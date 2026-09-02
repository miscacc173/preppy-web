<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{AuthController,
    GuestDemoController,
    GuestDemoEmailVerificationController,
    UserController,
    ExamBoardController,
    CourseController,
    SubjectController,
    TopicController,
    InstituteController,
    InstituteMemberController,
    InstituteStudentController,
    StudentOwnershipController,
    ParentStudentController,
    PlanController,
    DiscountController,
    InstitutePlanPriceController,
    CommissionRuleController,
    NotificationPreferenceController,
    UserDeviceController,
    SubscriptionController,
    BillingController,
    CommissionPayoutController,
    RoleController,
    PermissionController,
    StudentController,
    AuditLogController};
use App\Http\Controllers\Api\TutorQuestionFeedbackController;
use App\Http\Controllers\Api\InstituteCommissionController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\AppStateController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\LearningSessionController;
use App\Http\Controllers\Api\EducatorInviteController;
use App\Http\Controllers\Api\EducatorAccessController;
use App\Http\Controllers\Api\TeachingClassController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\InstitutePromoCodeController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\BillingDocumentController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\AuthEmailVerificationController;
use App\Http\Controllers\Api\AdminInvitationController;
use App\Http\Controllers\Api\AdminPromotionReferralController;
use App\Http\Controllers\Api\AppleIapController;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/register/email-verification', [AuthEmailVerificationController::class, 'request'])->middleware('throttle:5,1');
    Route::post('auth/register/email-verification/{challenge}/verify', [AuthEmailVerificationController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::get('catalog', [CatalogController::class, 'index']);
    Route::post('contact-messages', [ContactMessageController::class, 'store'])->middleware('throttle:10,1');
    Route::post('guest-demos/email-verification', [GuestDemoEmailVerificationController::class, 'request'])->middleware('throttle:5,1');
    Route::post('guest-demos/email-verification/{challenge}/verify', [GuestDemoEmailVerificationController::class, 'verify'])->middleware('throttle:10,1');
    Route::post('guest-demos', [GuestDemoController::class, 'start']);
    Route::get('guest-demos/{guest}', [GuestDemoController::class, 'show']);
    Route::put('guest-demos/{guest}/progress', [GuestDemoController::class, 'progress']);
    Route::post('guest-demos/{guest}/convert', [GuestDemoController::class, 'convert']);
    Route::post('institute-invites/accept', [InstituteMemberController::class, 'accept']);
    Route::get('institute-invites/{token}', [InstituteMemberController::class, 'showInvite']);
    Route::get('parent-invites/{token}', [ParentStudentController::class, 'showInvite']);
    Route::post('parent-invites/{token}/activate', [ParentStudentController::class, 'activateInvite']);
    Route::get('educator-invites/{token}', [EducatorInviteController::class, 'show']);
    Route::post('educator-invites/{token}/accept', [EducatorInviteController::class, 'accept']);
    Route::post('payments/callbacks/jazzcash', [PaymentCallbackController::class, 'jazzCash'])->middleware('throttle:60,1');
    Route::post('apple-iap/notifications', [AppleIapController::class, 'notifications'])->middleware('throttle:120,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('account', [AccountController::class, 'show']);
        Route::patch('account', [AccountController::class, 'update']);
        Route::put('account/notification-preferences', [AccountController::class, 'updateNotificationPreferences']);
        Route::get('app-state', [AppStateController::class, 'index']);
        Route::put('app-state/{key}', [AppStateController::class, 'update']);
        Route::delete('app-state/{key}', [AppStateController::class, 'destroy']);
        Route::apiResources(['users' => UserController::class, 'institutes' => InstituteController::class, 'students' => StudentController::class]);
        Route::put('users/{user}/roles', [UserRoleController::class, 'update'])->middleware('permission:roles.manage');
        Route::middleware('permission:catalog.manage')->group(function () {
            Route::apiResources(['exam-boards' => ExamBoardController::class, 'courses' => CourseController::class, 'subjects' => SubjectController::class, 'topics' => TopicController::class]);
        });
        Route::middleware('permission:plans.manage')->group(function () {
            Route::apiResources(['plans' => PlanController::class, 'discounts' => DiscountController::class, 'institute-plan-prices' => InstitutePlanPriceController::class]);
        });
        Route::middleware('permission:roles.manage')->group(function () {
            Route::apiResources(['roles' => RoleController::class, 'permissions' => PermissionController::class]);
        });
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('admin/invitations', [AdminInvitationController::class, 'index']);
            Route::post('admin/invitations/{type}/{id}/activate', [AdminInvitationController::class, 'activate']);
            Route::post('admin/invitations/{type}/{id}/link', [AdminInvitationController::class, 'link']);
            Route::post('admin/invitations/{type}/{id}/resend', [AdminInvitationController::class, 'resend']);
            Route::post('admin/invitations/{type}/{id}/extend', [AdminInvitationController::class, 'extend']);
            Route::delete('admin/invitations/{type}/{id}', [AdminInvitationController::class, 'revoke']);
            Route::apiResources(['notification-preferences' => NotificationPreferenceController::class, 'devices' => UserDeviceController::class]);
            Route::get('contact-messages', [ContactMessageController::class, 'index']);
            Route::patch('contact-messages/{contactMessage}', [ContactMessageController::class, 'update']);
        });
        Route::middleware('permission:commissions.manage')->group(function () {
            Route::apiResources(['commission-rules' => CommissionRuleController::class]);
            Route::get('institute-commissions', [InstituteCommissionController::class, 'index']);
        });
        Route::get('institutes/{institute}/members', [InstituteMemberController::class, 'index']);
        Route::post('institutes/{institute}/members/invite', [InstituteMemberController::class, 'invite']);
        Route::put('institutes/{institute}/members/{user}', [InstituteMemberController::class, 'update']);
        Route::delete('institutes/{institute}/members/{user}', [InstituteMemberController::class, 'destroy']);
        Route::get('institutes/{institute}/available-student', [InstituteStudentController::class, 'available']);
        Route::post('institutes/{institute}/students', [InstituteStudentController::class, 'store']);
        Route::post('institutes/{institute}/students/{student}/attach', [StudentOwnershipController::class, 'attach']);
        Route::post('students/{student}/detach-institute', [StudentOwnershipController::class, 'detach']);
        Route::get('parent-students', [ParentStudentController::class, 'index']);
        Route::get('students/{student}/parents', [ParentStudentController::class, 'forStudent']);
        Route::post('students/{student}/parents', [ParentStudentController::class, 'store']);
        Route::post('parent-student-links/{link}/accept', [ParentStudentController::class, 'accept']);
        Route::delete('parent-student-links/{link}', [ParentStudentController::class, 'destroy']);
        Route::post('educator-invites', [EducatorInviteController::class, 'store']);
        Route::get('educators/{educator}/access-grants', [EducatorAccessController::class, 'index']);
        Route::post('educators/{educator}/access-grants', [EducatorAccessController::class, 'store']);
        Route::delete('educators/{educator}/access-grants/{grant}', [EducatorAccessController::class, 'destroy']);
        Route::apiResource('institutes.teaching-classes', TeachingClassController::class)->except(['show']);
        Route::get('apple-iap/products', [AppleIapController::class, 'products']);
        Route::post('apple-iap/verify', [AppleIapController::class, 'verify']);
        Route::get('subscriptions', [SubscriptionController::class, 'index']);
        Route::post('subscriptions', [SubscriptionController::class, 'store']);
        Route::post('subscriptions/{subscription}/change-plan/preview', [SubscriptionController::class, 'previewPlanChange']);
        Route::post('subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan']);
        Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume']);
        Route::post('subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause']);
        Route::post('subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate']);
        Route::get('invoices', [BillingController::class, 'invoices']);
        Route::get('billing/entitlements', [BillingController::class, 'entitlements']);
        Route::get('payments', [BillingController::class, 'payments']);
        Route::get('refunds', [RefundController::class, 'index']);
        Route::post('invoices', [BillingController::class, 'createInvoice']);
        Route::post('invoices/{invoice}/payments', [BillingController::class, 'recordPayment']);
        Route::post('checkout/subscriptions', [CheckoutController::class, 'subscription']);
        Route::post('invoices/{invoice}/checkout', [CheckoutController::class, 'invoice']);
        Route::post('promotions/validate', [PromotionController::class, 'validateCode']);
        Route::get('referrals/me', [ReferralController::class, 'me']);
        Route::get('admin/promotions-referrals', [AdminPromotionReferralController::class, 'index']);
        Route::get('admin/promotions-referrals/export', [AdminPromotionReferralController::class, 'export']);
        Route::patch('admin/institute-promo-codes/{promoCode}/status', [AdminPromotionReferralController::class, 'updatePromoStatus']);
        Route::get('institutes/{institute}/promo-codes', [InstitutePromoCodeController::class, 'index']);
        Route::post('institutes/{institute}/promo-codes', [InstitutePromoCodeController::class, 'store']);
        Route::put('institutes/{institute}/promo-codes/{promoCode}', [InstitutePromoCodeController::class, 'update']);
        Route::delete('institutes/{institute}/promo-codes/{promoCode}', [InstitutePromoCodeController::class, 'destroy']);
        Route::get('institutes/{institute}/promo-codes/{promoCode}/report', [InstitutePromoCodeController::class, 'report']);
        Route::post('payments/{payment}/capture/google-pay', [CheckoutController::class, 'captureGooglePay']);
        Route::post('payments/{payment}/capture/mock', [CheckoutController::class, 'captureMock']);
        Route::get('payments/{payment}', [CheckoutController::class, 'showPayment']);
        Route::post('payments/{payment}/refunds', [RefundController::class, 'store']);
        Route::get('invoices/{invoice}/pdf', [BillingDocumentController::class, 'invoice']);
        Route::get('payments/{payment}/receipt', [BillingDocumentController::class, 'receipt']);
        Route::get('refunds/{refund}/receipt', [BillingDocumentController::class, 'refundReceipt']);
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
        Route::get('tutor-question-feedback', [TutorQuestionFeedbackController::class, 'index']);
        Route::post('tutor-question-feedback', [TutorQuestionFeedbackController::class, 'store']);
        Route::delete('tutor-question-feedback/{feedback}', [TutorQuestionFeedbackController::class, 'destroy']);
        Route::get('learning-sessions', [LearningSessionController::class, 'index']);
        Route::post('learning-sessions', [LearningSessionController::class, 'store'])->middleware('subscription:learning');
        Route::put('learning-sessions/{sessionUid}/progress', [LearningSessionController::class, 'progress'])->middleware('subscription:learning');
        Route::get('teacher/students', [LearningSessionController::class, 'teacherStudents']);
        Route::get('tutor/review-queue', [LearningSessionController::class, 'tutorReviewQueue']);
        Route::get('tutor/academic-access', [LearningSessionController::class, 'tutorAcademicAccess']);
        Route::get('commission-payouts', [CommissionPayoutController::class, 'index']);
        Route::post('commission-payouts', [CommissionPayoutController::class, 'store']);
        Route::post('commission-payouts/{payout}/mark-paid', [CommissionPayoutController::class, 'markPaid']);
        Route::get('institutes/{institute}/settlements', [InstituteCommissionController::class, 'forInstitute']);
        Route::get('institutes/{institute}/platform-fees', [InstituteCommissionController::class, 'platformFees']);
        Route::get('institutes/{institute}/earnings', [InstituteCommissionController::class, 'earnings']);
    });
});
