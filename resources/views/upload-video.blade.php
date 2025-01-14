<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Загрузка видео</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Загрузка видео</h1>

        <div id="alert" class="alert d-none" role="alert"></div>

        <form id="videoUploadForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="video" class="form-label">Выберите видео:</label>
                <input type="file" class="form-control" name="video" id="video" accept="video/*" required>
            </div>
            <button type="submit" class="btn btn-primary" id="uploadButton">Загрузить</button>
            <div id="loadingIcon" class="spinner-border text-primary ms-3 d-none" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
        </form>

        <div id="processingStatus" class="mt-4 d-none">
            <h4>Обработка видео... <div class="spinner-border" role="status"></div>
            </h4>
        </div>
        <div id="violationsOutput"></div>
    </div>
    </div>

    <script>
        $(document).ready(function () {
            statusInterval = null;
            clearInterval(statusInterval);
            $('#videoUploadForm').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                $('#uploadButton').prop('disabled', true);
                $('#loadingIcon').removeClass('d-none');

                $.ajax({
                    url: "{{ route('video.upload.store') }}",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#alert').removeClass('d-none alert-danger').addClass('alert-success').text('Видео успешно загружено!');
                        $('#processingStatus').removeClass('d-none');
                        checkProcessingStatus(response.video.id);
                    },
                    error: function (xhr) {
                        $('#alert').removeClass('d-none alert-success').addClass('alert-danger').text('Ошибка загрузки видео.');
                    },
                    complete: function () {
                        $('#uploadButton').prop('disabled', false);
                        $('#loadingIcon').addClass('d-none');
                    }
                });
            });

            function checkProcessingStatus(videoId) {
                statusInterval = setInterval(function () {
                    $.ajax({
                        url: "/video-status/" + videoId,
                        type: "GET",
                        success: function (response) {
                            if (response.video.status === 'violation_found') {
                                $('#processingStatus').text('Видео успешно обработано. Найдены нарушения');
                                clearInterval(statusInterval);

                                // Вывод нарушений
                                if (response.video.violations && response.video.violations.length > 0) {
                                    let violationsHtml = '<h3>Найденные нарушения:</h3><ul class="violations-list">';
                                    response.video.violations.forEach(function (violation) {
                                        violationsHtml += '<li>';
                                        violationsHtml += '<p>Тип нарушения: ' + violation.violation_type + '</p>';
                                        if (violation.image_path) {
                                            violationsHtml += '<div class="violation-image">';
                                            violationsHtml += '<img src="/output/' + violation.image_path + '" alt="Изображение нарушения" style="max-width: 100%; height: auto;">';
                                            violationsHtml += '</div>';
                                        }
                                        violationsHtml += '</li>';
                                    });
                                    violationsHtml += '</ul>';
                                    $('#violationsOutput').html(violationsHtml).removeClass('d-none');
                                }
                            } else if (response.video.status === 'violation_not_detected') {
                                $('#processingStatus').text('Видео успешно обработано. Нарушений не найдено');
                                clearInterval(statusInterval);
                            } else if (response.video.status === 'error') {
                                $('#processingStatus').text('Ошибка обработки видео.');
                                clearInterval(statusInterval);
                            }
                        },
                        error: function () {
                            $('#processingStatus').text('Ошибка получения статуса.');
                            clearInterval(statusInterval);
                        }
                    });
                }, 3000); // Проверяем каждые 3 секунды
            }
        });

    </script>
</body>

</html>