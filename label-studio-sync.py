import os
import argparse
import mysql.connector
from dotenv import load_dotenv
from label_studio_sdk import Client
from datetime import datetime
from dateutil.parser import isoparse
from dateutil.tz import gettz

# Загрузка параметров из .env
load_dotenv()
DB_HOST = os.getenv("DB_HOST", 'localhost')
DB_USER = os.getenv("DB_USERNAME")
DB_PASSWORD = os.getenv("DB_PASSWORD")
DB_NAME = os.getenv("DB_DATABASE")
LABEL_STUDIO_URL = os.getenv("LABEL_STUDIO_URL", "http://localhost:8080")
LABEL_STUDIO_TOKEN = os.getenv("LABEL_STUDIO_TOKEN", "")

# Подключение к Label Studio
ls = Client(url=LABEL_STUDIO_URL, api_key=LABEL_STUDIO_TOKEN)

# Подключение к MySQL


def connect_db():
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )


# Создание таблиц
CREATE_TABLES = [
    """
    CREATE TABLE IF NOT EXISTS ls_users (
        id INT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )
    """,
    """
    CREATE TABLE IF NOT EXISTS ls_projects (
        id INT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        color VARCHAR(7) DEFAULT NULL
    )
    """,
    """
    CREATE TABLE IF NOT EXISTS ls_annotations (
        id INT PRIMARY KEY,
        project_id INT,
        user_id INT,
        created_at DATETIME,
        updated_at DATETIME,
        object_count INT,
        FOREIGN KEY (project_id) REFERENCES ls_projects(id),
        FOREIGN KEY (user_id) REFERENCES ls_users(id)
    )
    """
]


def setup_database():
    connection = connect_db()
    cursor = connection.cursor()
    for query in CREATE_TABLES:
        cursor.execute(query)
    connection.commit()
    connection.close()

# Проверка и добавление пользователей в базу


def sync_users(connection, users):
    cursor = connection.cursor()
    for user in users:
        cursor.execute(
            "INSERT INTO ls_users (id, name) VALUES (%s, %s) ON DUPLICATE KEY UPDATE name=VALUES(name)",
            (user.id, f"{user.first_name} {user.last_name}  {user.email}")
        )
    connection.commit()

# Проверка и добавление проектов в базу


def sync_projects(connection, projects):
    projects_to_update = []
    cursor = connection.cursor()
    for project in projects:
        cursor.execute(
            "SELECT color FROM ls_projects WHERE id = %s", (project.id,)
        )
        result = cursor.fetchone()
        if result and result[0] == "#9AC422":
            continue
        projects_to_update.append(project)
        cursor.execute(
            "INSERT INTO ls_projects (id, name, color) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE name=VALUES(name), color=VALUES(color)",
            (project.id, project.title, project.color)
        )
    connection.commit()
    return projects_to_update

# Сохранение аннотаций в базу


def update_annotations(projects, connection, start_date=None, end_date=None):
    timezone = gettz('Asia/Tashkent')
    cursor = connection.cursor()
    for project in projects:
        tasks = project.get_tasks()
        for task in tasks:
            for annotation in task.get("annotations", []):
                created_at = isoparse(annotation.get(
                    "created_at")).astimezone(timezone)
                updated_at = isoparse(annotation.get(
                    "updated_at")).astimezone(timezone)

                if start_date and created_at < start_date:
                    continue
                if end_date and created_at > end_date:
                    continue
                cursor.execute(
                    """
                    INSERT INTO ls_annotations (id, project_id, user_id, created_at, updated_at, object_count)
                    VALUES (%s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                    updated_at=VALUES(updated_at), object_count=VALUES(object_count)
                    """,
                    (
                        annotation["id"],
                        project.id,
                        annotation.get("completed_by", "unknown"),
                        created_at,
                        updated_at,
                        len(annotation.get("result", []))
                    )
                )
            connection.commit()


# Основной код
if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Sync Label Studio data with MySQL")
    parser.add_argument("--start-date", type=lambda d: datetime.strptime(
        d, '%Y-%m-%d').date(), help="Start date for filtering annotations (YYYY-MM-DD)")
    parser.add_argument("--end-date", type=lambda d: datetime.strptime(
        d, '%Y-%m-%d').date(), help="End date for filtering annotations (YYYY-MM-DD)")
    args = parser.parse_args()

    setup_database()
    connection = connect_db()

    try:
        # Получение пользователей
        users = ls.get_users()
        sync_users(connection, users)

        # Получение проектов
        projects = ls.get_projects()
        projects_to_update = sync_projects(connection, projects)

        # Сбор аннотаций с фильтрацией по дате
        start_date = args.start_date
        end_date = args.end_date
        update_annotations(projects_to_update, connection,
                           start_date, end_date)

        # Сохранение аннотаций в базу

    finally:
        connection.close()
