<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\General\Notification\CreateNotificationRequest;
use App\Http\Resources\Core\NotificationResource;
use App\Repositories\Core\NotificationRepository;
use App\Services\CoreServices\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * Get all school notifications
     */
    public function getAllNotifications(Request $request)
    {
        $per_page = $request->query('perPage', 10);
        $owner_id = auth()->user()->activeSchool->id;
        $notifications = $this->notificationService->getAllNotifications($owner_id, $per_page);

        return respondSuccess('notifications fetched successful ', paginateResource($notifications, NotificationResource::class));
    }

    /**
     * Get notification count
     */
    public function getNotificationCounts()
    {
        $owner_id = auth()->user()->activeSchool->id;

        return respondSuccess('notification count fetched successful ', $this->notificationService->getNotificationCounts($owner_id));
    }

    /**
     * Create notification
     *
     * @bodyParam title string required
     * @bodyParam body string required
     * @bodyParam preview string
     * @bodyParam markup_body string required
     * @bodyParam type string
     */
    public function createNotification(CreateNotificationRequest $request)
    {
        $validated = (object) $request->validated();

        $data = [
            'title' => $validated->title,
            'body' => $validated->body,
            'preview' => $validated->preview,
            'markup_body' => $validated->markup_body ?? null,
            'type' => $validated->type ?? null,
            'owner_id' => auth()->user()->activeSchool->id,
            'owner' => 'school',
            'sent_by' => 'system',
        ];

        $created = $this->notificationService->createNotification($data);

        if (!$created) {
            return respondError('Notification could not be created');
        }

        return respondSuccess('Notification created successfully', $created);
    }

    /**
     * Get a notification
     *
     * @pathParam id string required
     */
    public function getNotification($id)
    {
        $notification = $this->notificationService->getNotificationById($id);

        if (!$notification) {
            return respondError('Notification not found', null, 404);
        }

        return respondSuccess('notification fetched successfully', $notification);
    }

    /**
     * Mark notification as read
     *
     * @pathParam id string required
     */
    public function markNotificationAsRead($id)
    {
        $notification = $this->notificationService->markAsRead($id);

        if (!$notification) {
            return respondError('Notification not found', null, 404);
        }

        return respondSuccess('Notification marked as read.', $notification);
    }

    /**
     * Mark all notifications as read
     *
     * @pathParam id string required
     */
    public function markAllNotificationsAsRead()
    {
        $owner_id = auth()->user()->activeSchool->id;
        $updated = $this->notificationService->markAllAsRead($owner_id);

        if (!$updated) {
            return respondError('Notifications could not be marked as read');
        }

        return respondSuccess('All notifications marked as read.');
    }

    /**
     * delete notification
     *
     * @pathParam id string required
     */
    public function deleteNotification($id)
    {
        $notification = $this->notificationService->getNotificationById($id);

        if (!$notification) {
            return respondError('Notification not found', null, 404);
        }

        $deleted = $this->notificationService->deleteNotification($id);

        if (!$deleted) {
            return respondError('Notification could not be deleted, try again');
        }

        return respondSuccess('Notification deleted successfully');
    }

    /**
     * delete all notifications
     *
     * @pathParam id string required
     */
    public function deleteAllNotifications()
    {
        $owner_id = auth()->user()->activeSchool->id;
        $deleted = $this->notificationService->deleteAll($owner_id);

        if (!$deleted) {
            return respondError('Notifications could not be deleted, try again');
        }

        return respondSuccess('notifications deleted successfully');
    }
}
