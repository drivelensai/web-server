<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user_id)
            return $this->fetchData($request);
        // Получение списка пользователей для формы
        $users = DB::table('ls_users')->select('id', 'name')->get();
        return view('report.index', compact('users'));
    }

    public function fetchData(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:ls_users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);


        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Ограничение срока одним месяцем
        if ($dateFrom && $dateTo) {
            $diff = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
            if ($diff > 31) {
                return redirect()->back()->withErrors(['date_to' => 'The date range must not exceed one month.']);
            }
        }

        $query = DB::table('ls_annotations')
            ->selectRaw("
                DATE(created_at) as date,
                ls_users.name,
                TIME(MIN(created_at)) as start_time,
                TIME(MAX(created_at)) as end_time,
                TIMEDIFF(TIME(MAX(created_at)), TIME(MIN(created_at))) as diff,
                COUNT(ls_annotations.id) as images_count,
                SUM(object_count) as objects_count
            ")
            ->leftJoin('ls_users', 'ls_users.id', '=', 'ls_annotations.user_id')
            ->when($request->user_id, function ($q) use ($request) {
                return $q->where('ls_annotations.user_id', $request->user_id);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                return $q->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                return $q->whereDate('created_at', '<=', $dateTo);
            })
            ->groupBy(DB::raw("CONCAT(DATE(created_at), user_id)"))
            ->orderBy('created_at')
            ->get();

        return view('report.index', [
            'users' => DB::table('ls_users')->select('id', 'name')->get(),
            'results' => $query,
        ]);
    }

}
