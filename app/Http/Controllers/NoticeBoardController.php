<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NoticeBoard;

class NoticeBoardController extends Controller
{
    private function noticeData(Request $request): array
    {
        $data = $request->only([
            'title',
            'notice',
            'created_by',
            'type',
            'color_code',
            'start_date',
            'end_date',
        ]);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('highlight')) {
            $data['highlight'] = $request->boolean('highlight');
        }

        return $data;
    }

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

        $notice->update($this->noticeData($request));

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
            $data = $this->noticeData($request);

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
