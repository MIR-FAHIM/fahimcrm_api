<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProductVarientController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductOrderController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\TaskTypeController;
use App\Http\Controllers\ContactUSFormController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductBrandController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\UserFeaturePermissionController;
use App\Http\Controllers\FeatureListController;
use App\Http\Controllers\TaskAssignedPersonsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SetAllLeaveController;
use App\Http\Controllers\TaskActivityController;
use App\Http\Controllers\TaskFollowupController;
use App\Http\Controllers\ProspectStageController;
use App\Http\Controllers\InformationSourceController;
use App\Http\Controllers\NoticeBoardController;
use App\Http\Controllers\InfluencingRoleController;
use App\Http\Controllers\ProductItemController;
use App\Http\Controllers\IndustryTypeController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\ProjectPhaseController;
use App\Http\Controllers\ProspectLogActivityController;
use App\Http\Controllers\AddProspectContactController;
use App\Http\Controllers\FacebookLeadsController;
use App\Http\Controllers\MeetingSetController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectTeamMatesController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\ProspectConcernPersonTeamController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ModulepermissionController;




use App\Http\Middleware\CheckAppToken;

//Route::post('/login', [UserController::class, 'login']);
Route::middleware([CheckAppToken::class])->group(function () {

   Route::post('/login', [UserController::class, 'login'])->withoutMiddleware([CheckAppToken::class]);
   


Route::post('/register-employee', [UserController::class, 'registerEmployee']);
Route::get('/logout', [UserController::class, 'logout']);
Route::get('/get-dashboard-report', [UserController::class, 'getDashBoardReport']);

Route::get('/get-all-employee', [UserController::class, 'getAllEmployees']);

Route::post('/upload-user-image', [UserController::class, 'uploadProfilePicture']);
Route::post('/update-userinfo', [UserController::class, 'updateInformation']);
Route::post('/change-password', [UserController::class, 'changePassword']);
Route::get('/get-profile', [UserController::class, 'getProfile']);
Route::post('/add-department', [DepartmentController::class, 'addDepartment']);
Route::get('/get-department', [DepartmentController::class, 'getDepartments']);
Route::get('/get-department-with-user', [DepartmentController::class, 'getDepartmentsWithEmp']);
Route::post('/add-designation', [DesignationsController::class, 'addDesignation']);
Route::get('/get-designation', [DesignationsController::class, 'getDesignations']);
Route::post('/add-role', [RoleController::class, 'addRole']);
Route::get('/get-role', [RoleController::class, 'getRole']);


//attendance
Route::post('/check-in-now', [AttendanceController::class, 'checkInNow']);
Route::post('/update-attendance', [AttendanceController::class, 'updateAttendance']);
Route::post('/approve-time-adjustment', [AttendanceController::class, 'approveTimeAdjustment']);
Route::post('/request-attendance-adjustment', [AttendanceController::class, 'requestAttendanceAdjustment']);
Route::post('/check-out-now', [AttendanceController::class, 'checkOutNow']);
Route::get('/get-attendance-date', [AttendanceController::class, 'getAttendancesByDate']);
Route::get('/get-attendance-adjustment', [AttendanceController::class, 'getAttendanceAdjustment']);
Route::get('/attendance-report-dashboard', [AttendanceController::class, 'dashboardAttendanceReport']);
Route::get('/is-checkedin-today', [AttendanceController::class, 'hasCheckedInToday']);
Route::post('/get-attendance-report-user', [AttendanceController::class, 'getAttendanceReportByMonth']);


//task started

Route::get('/get-priorites', [PriorityController::class, 'getPriorites']);
Route::post('/add-priority', [PriorityController::class, 'addPriority']);

Route::get('/get-task-type', [TaskTypeController::class, 'getTaskType']);
Route::post('/add-task-type', [TaskTypeController::class, 'addTaskType']);

Route::get('/get-task-status', [TaskStatusController::class, 'getTaskStatus']);
Route::post('/add-task-status', [TaskStatusController::class, 'addTaskStatus']);

///project_______
Route::get('/get-project-details/{id}', [ProjectController::class, 'getProjectDetails']);
Route::get('/get-project', [ProjectController::class, 'getProject']);
Route::post('/add-project', [ProjectController::class, 'addProject']);

Route::post('/add-project-phase', [ProjectPhaseController::class, 'addProjectPhase']);
Route::get('/get-project-phase/{id}', [ProjectPhaseController::class, 'getPhaseByPrjId']);
Route::post('/update-phase/{id}', [ProjectPhaseController::class, 'updatePhaseByID']);

//task

Route::get('/get-all-task', [TasksController::class, 'getAllTask']);
Route::get('/get-all-task-by-status', [TasksController::class, 'getAllTaskByStatus']);
Route::get('/task-by-project-phase/{id}', [TasksController::class, 'getPhaseTaskByStatus']);
Route::get('/task-by-project/{id}', [TasksController::class, 'getProjectTaskByStatus']);
Route::get('/task-details/{id}', [TasksController::class, 'showTaskDetails']);
Route::get('/delete-task', [TasksController::class, 'deleteTask']);
Route::get('/get-user-task/{id}', [TasksController::class, 'getTaskByUserId']);
Route::get('/get-task-report/{id}', [TasksController::class, 'getTaskReportByUser']);
Route::post('/add-task', [TasksController::class, 'addTask']);
Route::post('/update-task', [TasksController::class, 'updateTask']);
Route::post('/update-completion-percentage', [TasksController::class, 'updateCompletionPercentage']);
Route::post('/update-show-completion-percentage', [TasksController::class, 'updateShowCompletionPercentage']);
Route::post('/update-task-status', [TasksController::class, 'updateStatus']);

Route::post('/assign-task', [TaskAssignedPersonsController::class, 'assignEmployeeToTask']);
Route::get('/get-assigned-task/{id}', [TaskAssignedPersonsController::class, 'getAssignedTaskByUserId']);

//task activity 
Route::post('/add-task-activity', [TaskActivityController::class, 'addTaskActivity']);
Route::get('/get-task-activitiesByTaskId/{id}', [TaskActivityController::class, 'getTaskActivitiesByTaskId']);
//and follow up
Route::post('/add-task-followup', [TaskFollowupController::class, 'addTaskFollowup']);
Route::get('/get-task-followupsByTaskId/{id}', [TaskFollowupController::class, 'getTaskFollowupsByTaskId']);

Route::post('/send-notification', [NotificationController::class, 'sendNotification']);
Route::post('/add-notification', [NotificationController::class, 'addNotificationApi']);
Route::get('/get-notifications/{id}', [NotificationController::class, 'getNotificationsByUser']);

// leave manager
Route::post('/set-leave-company', [SetAllLeaveController::class, 'setLeave']);
Route::post('/add-leave', [SetAllLeaveController::class, 'addLeave']);
Route::post('/approve-leave/{id}', [SetAllLeaveController::class, 'approveLeave']);
Route::post('/reject-leave/{id}', [SetAllLeaveController::class, 'rejectLeave']);
Route::get('/get-emp-leave/{id}', [SetAllLeaveController::class, 'getLeavesByUserId']);
Route::get('/get-leave-type', [SetAllLeaveController::class, 'getLeaveOfCompany']);
Route::get('/get-emp-leave-report/{id}', [SetAllLeaveController::class, 'getLeaveStatusByUserId']);
Route::get('/get-all-leave', [SetAllLeaveController::class, 'getAllLeave']);

// prospect-stage
Route::post('/add-prospect-stage', [ProspectStageController::class, 'addProspectStage']);
Route::post('/prospectstage-by-log-and-prospect', [ProspectStageController::class, 'getProspectStageListWithLogs']);
Route::get('/get-prospect-stages', [ProspectStageController::class, 'getProspectStage']);


Route::post('/add-information-source', [InformationSourceController::class, 'addInformationSource']);
Route::get('/get-information-source', [InformationSourceController::class, 'getInformationSources']);

Route::post('/add-industry-type', [IndustryTypeController::class, 'addIndustryType']);
Route::get('/get-industry-type', [IndustryTypeController::class, 'getIndustryType']);

// Prospect Controller ----- Prospect *******


Route::get('/get-prospect-by-stage', [ProspectController::class, 'getProspectByStage']);
Route::get('/get-prospect-month-report', [ProspectController::class, 'getMonthlyOnboardedProspects']);
Route::get('/get-prospect-source-wise', [ProspectController::class, 'getProspectByInformationSource']);
Route::get('/get-warehouse', [ProspectController::class, 'getAllWarehouse']);
Route::get('/get-prospect-weekly-report', [ProspectController::class, 'getWeeklyOnboardedProspects']);
Route::post('/create-prospect', [ProspectController::class, 'createProspect']);
Route::post('/check-prospectname-avaiblity', [ProspectController::class, 'checkProspectAvailable']);
Route::get('/get-all-prospect', [ProspectController::class, 'getAllProspect']);
Route::get('/get-prospect-detail/{id}', [ProspectController::class, 'getProspectDetail']);
Route::get('/get-prospect-stage-overview', [ProspectController::class, 'getAllProspectStageOverview']);
Route::get('/get-individual-prospect', [ProspectController::class, 'getIndividualProspect']);
Route::get('/get-organization-prospect', [ProspectController::class, 'getOrganizationProspect']);
Route::post('/change-prospect-stage', [ProspectController::class, 'changeProspectStage']);
Route::post('/update-prospect', [ProspectController::class, 'updateProspect']);
Route::post('/delete-prospect', [ProspectController::class, 'deleteProspect']);

//prospect log activity
Route::post('/add-prospect-log-activity', [ProspectLogActivityController::class, 'addProspectLogActivity']);
Route::get('/get-log-activity-by-prospect/{id}', [ProspectLogActivityController::class, 'getLogByProspectId']);
Route::get('/calculate-effort-prospect', [ProspectLogActivityController::class, 'calculateEffort']);
Route::get('/delete-log-prospect/{id}', [ProspectLogActivityController::class, 'deleteLog']);
Route::post('/update-log-prospect/{id}', [ProspectLogActivityController::class, 'updateLog']);
// prospect contact person


Route::post('/add-prospect-contact-person', [AddProspectContactController::class, 'addMultipleContactPerson']);
Route::get('/get-contact-person-prospect/{id}', [AddProspectContactController::class, 'getContactPersonByProspectId']);


// marketing -facebook
Route::get('/get-facebook-leads', [FacebookLeadsController::class, 'getFacebookLeads']);
Route::post('/convert-to-prospect', [ProspectController::class, 'convertToProspect']);
Route::post('/update-contact-status-facebook', [FacebookLeadsController::class, 'updateStatusForMultiple']);


// Meeting

Route::post('/add-meeting', [MeetingSetController::class, 'addMeeting']);
Route::get('/get-all-meeting', [MeetingSetController::class, 'getAllMeetings']);

Route::get('/get-meeting-by-user/{id}', [MeetingSetController::class, 'getMeetingByUser']);
Route::get('/get-meeting-by-prospect/{id}', [MeetingSetController::class, 'getMeetingByProspect']);


//CLient +++++++++++++++
Route::post('/add-client', [ClientController::class, 'addClient']);
Route::get('/clients', [ClientController::class, 'getAllClients']);
Route::get('/get-client-details/{id}', [ClientController::class, 'getClientDetails']);


///Project TeamMates Controller__________________
Route::post('/project-members/add-multiple', [ProjectTeamMatesController::class, 'addMultipleProjectMembers']);
Route::get('/project-members/{project_id}', [ProjectTeamMatesController::class, 'getMemberByProjectID']);
Route::delete('/project-members/{id}', [ProjectTeamMatesController::class, 'removeMember']);
Route::put('/project-members/{id}/notify-active', [ProjectTeamMatesController::class, 'updateNotifyActiveForMember']);

//ChatMessageController -----------------
Route::post('/conversation-room/add', [ChatMessageController::class, 'addConversationRoom']);
Route::post('/add-chat', [ChatMessageController::class, 'addChat']);
Route::post('/get-chat-by-projectId', [ChatMessageController::class, 'getChatByProjectId']);
Route::post('/get-chat-by-conversationid', [ChatMessageController::class, 'getChatByConversationId']);
Route::get('/conversation-room', [ChatMessageController::class, 'getConversationRoom']);

//ZoneController
Route::post('/zone/add', [ZoneController::class, 'addZone']);
Route::get('/zones', [ZoneController::class, 'getZones']);

//InfluencingRoleController
Route::post('/add-influencing-role', [InfluencingRoleController::class, 'addInfluenceRole']);
Route::get('/influencing-roles', [InfluencingRoleController::class, 'getInfluenceRoles']);

//ProspectConcernPersonTeamController--------------------------
Route::post('/prospect-concern-person/add', [ProspectConcernPersonTeamController::class, 'addConcernPersons']);
Route::get('/prospect-concern-person/{prospect_id}', [ProspectConcernPersonTeamController::class, 'getConcernPersons']);
Route::post('/prospect-concern-person/remove', [ProspectConcernPersonTeamController::class, 'removeConcernPerson']);


//ReportController
Route::get('/report-text', [ReportController::class, 'reportText']);


// ------product controller


Route::post('/product/add', [ProductItemController::class, 'addProduct']);
Route::get('/product/active', [ProductItemController::class, 'getActiveProduct']);
Route::get('/product/active/variants', [ProductItemController::class, 'getActiveProductWithVariants']);


// --------- contact us ------

Route::post('/add-contact-us', [ContactUSFormController::class, 'addContactUsData']);
Route::get('/get-contact-us', [ContactUSFormController::class, 'getContactUs']);
Route::post('/update-contact-status', [ContactUSFormController::class, 'updateStatusForMultiple']);


// --------------- OpportunityController
Route::post('/add-opportunity', [OpportunityController::class, 'addOpportunity']);
Route::get('/get-opportunities', [OpportunityController::class, 'getOpportunities']);
Route::get('/get-opportunities-by-stage', [OpportunityController::class, 'getOpportunitiesByStage']);
Route::post('/change-opportunity-status', [OpportunityController::class, 'changeOpportunityStatus']);
Route::post('/update-opportunity/{id}', [OpportunityController::class, 'updateOpportunity']);
Route::get('/details-opportunity/{id}', [OpportunityController::class, 'getOpportunityDetails']);

// quotation COntroller
Route::post('/add-quotation', [QuotationController::class, 'addQuote']);
Route::get('/quotations', [QuotationController::class, 'getQuotes']);
Route::delete('/quotation/{id}', [QuotationController::class, 'removeQuote']);
Route::put('/quotation/{id}', [QuotationController::class, 'updateQuote']);
Route::patch('/quotation/{id}/approval', [QuotationController::class, 'updateApproval']);
Route::get('/quotations/prospect/{prospect_id}', [QuotationController::class, 'getQuotesByProspectId']);

//ProductVarientController

Route::prefix('product-variant')->group(function () {
    Route::post('/add', [ProductVarientController::class, 'addMultipleVariants']);
    Route::get('/all/{product_id}', [ProductVarientController::class, 'getAllVariantByProductId']);
    Route::delete('/delete/{id}', [ProductVarientController::class, 'deleteVariant']);
    Route::get('/total-quantity/{product_id}', [ProductVarientController::class, 'getTotalQuantityOfProduct']);
    Route::get('/stock-wise/{product_id}', [ProductVarientController::class, 'getStockWiseProduct']);
    Route::put('/update/{id}', [ProductVarientController::class, 'updateVariant']);
});
//ProductCategoryController
Route::post('/add-category', [ProductCategoryController::class, 'addCategory']);
Route::get('/get-active-categories', [ProductCategoryController::class, 'getActiveCategory']);
Route::put('/update-category/{id}', [ProductCategoryController::class, 'updateCategory']);
Route::delete('/remove-category/{id}', [ProductCategoryController::class, 'removeCategory']);

//ProductBrandController
Route::post('/add-brand', [ProductBrandController::class, 'addBrand']);
Route::get('/get-brands', [ProductBrandController::class, 'getBrand']);
Route::put('/update-brand/{id}', [ProductBrandController::class, 'updateBrand']);
Route::delete('/delete-brand/{id}', [ProductBrandController::class, 'deleteBrand']);


Route::prefix('product-orders')->group(function () {
    Route::get('/get-order', [ProductOrderController::class, 'getOrder']);           // GET /api/product-orders
    Route::post('/add-order', [ProductOrderController::class, 'store']);          // POST /api/product-orders
    Route::get('/{id}', [ProductOrderController::class, 'show']);        // GET /api/product-orders/{id}
    Route::put('/{id}', [ProductOrderController::class, 'update']);      // PUT /api/product-orders/{id}
    Route::delete('/{id}', [ProductOrderController::class, 'destroy']);  // DELETE /api/product-orders/{id}
});

// --------------- StockController
Route::post('/stock/add', [StockController::class, 'addStock']);
Route::get('/stock/list', [StockController::class, 'getStock']);
// --------------- system module controller

Route::get('/module/permission/{id}', [ModulepermissionController::class, 'getPermissionsByCompany']);

// NoticeBoardController  ------------- 
Route::post('/notice/add', [NoticeBoardController::class, 'addNotice']);
Route::get('/notice/all', [NoticeBoardController::class, 'getAllNotices']);



//   FeatureListController

Route::post('/add-feature', [FeatureListController::class, 'addFeature']);
Route::get('/active-features', [FeatureListController::class, 'getActiveFeature']);



// UserFeaturePermissionController;

Route::post('/add-feature-permission', [UserFeaturePermissionController::class, 'addFeaturePermission']);
Route::get('/user-feature-permissions/{user_id}', [UserFeaturePermissionController::class, 'getFeaturePermissionByUser']);
Route::post('/update-feature-permission', [UserFeaturePermissionController::class, 'updateSingleFeaturePermission']);

});