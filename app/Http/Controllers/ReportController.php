<?php

namespace App\Http\Controllers;
use App\Models\Prospect;
use App\Models\Tasks;
use App\Models\InformationSource;
use App\Models\Attendance;
use App\Models\ProspectStage;
use App\Models\ProspectLogActivity;
use App\Models\TaskFollowup;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function reportText(Request $request)
    {
        // Example static data – replace with real data later
        $data = [
            'start_date'=> '2025-04-10 13:31:25',
            'end_date'=> '2025-04-14 13:31:25',
            'reporting_period' => 'April 1 – April 30, 2025',
            'leads_added' => 156,
            'top_sources' => ['Facebook Ads (42%)', 'Website Form (35%)', 'Referral (18%)'],
            'prospects_engaged' => 98,
            'clients_converted' => 34,
            'conversion_rate' => '21.8%',
            'calls_made' => 182,
            'emails_sent' => 213,
            'messages_sent' => 145,
            'followups' => 67,
            'tasks_created' => 132,
            'tasks_completed' => 113,
            'overdue_tasks' => 8,
            'projects' => [
                'ongoing' => 4,
                'completed' => 2,
            ],
            'top_performer' => 'Ayesha Rahman',
            'working_days' => 22,
            'attendance_avg' => '91%',
            'late_entries' => 14,
            'top_attendee' => 'Nazmul Hasan',
            'leads_to_prospects' => '62%',
            'prospects_to_clients' => '35%',
            'avg_days_to_convert' => 11,
            'stalled_leads' => 23,
            'recommendations' => [
                'Follow-up on all leads aged 7–14 days.',
                'Improve task completion in the operations team.',
                'Reward top performers to encourage morale.'
            ]
        ];
    
        return response()->json(
            [
                'status'=>'success',
                'data'=>$data]
            );
    }
    
}
