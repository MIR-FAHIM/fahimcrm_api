<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NoticeBoard;

class NoticeBoardController extends Controller
{
    public function getAllNotices()
    {
        try {
            $notices = NoticeBoard::orderBy('start_date', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data'   => $notices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'data'   => null,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    public function updateNotice(Request $request)
{
    try {
        $notice = NoticeBoard::find($request->id);

        if (!$notice) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Notice not found',
            ]);
        }

        $notice->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Notice updated successfully',
            'data' => $notice,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'error' => $e->getMessage(),
        ]);
    }
}
public function deleteNotice(Request $request)
{
    try {
        $notice = NoticeBoard::find($request->id);

        if (!$notice) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Notice not found',
            ]);
        }

        $notice->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notice deleted successfully',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'failed',
            'error' => $e->getMessage(),
        ]);
    }
}

    public function addNotice(Request $request)
    {
        try {
            $data = $request->only([
                'title',
                'notice',
                'created_by',
                'type',
                'is_active',
                'highlight',
                'color_code',
                'start_date',
                'end_date',
            ]);

            $notice = NoticeBoard::create($data);

            return response()->json([
                'status' => 'success',
                'data'   => $notice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'data'   => null,
                'error'  => $e->getMessage(),
            ]);
        }
    }
    
}
