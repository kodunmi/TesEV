<?php

namespace App\Services\CoreServices;

use App\Repositories\Core\NotificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(protected NotificationRepository $notificationRepository)
    {
    }

    public function getAllNotifications($owner_id, $per_page)
    {
        $notifications = $this->notificationRepository->getAllNotifications($owner_id, $per_page);

        return $notifications;
    }

    public function getNotificationCounts($owner_id)
    {
        return $this->notificationRepository->notificationCounts($owner_id);
    }

    public function getNotificationById($id)
    {
        if (!isUuid($id)) {
            return null;
        }

        return $this->notificationRepository->findById($id);
    }

    public function createNotification(array $data)
    {
        return $this->notificationRepository->create($data);
    }

    public function updateNotification($id, array $data)
    {
        return $this->notificationRepository->update($id, $data);
    }

    public function deleteNotification($id)
    {
        return $this->notificationRepository->delete($id);
    }

    public function markAllAsRead($owner_id)
    {
        $notifications = $this->notificationRepository->getAllNotifications($owner_id);

        foreach ($notifications as $notification) {
            $read_by = $notification->read_by ?? [];

            if (!in_array($owner_id, $read_by)) {
                $read_by[] = $owner_id;

                $notification->update([
                    'is_read' => true,
                    'read_at' => now(),
                    'read_by' => $read_by,
                ]);
            }
        }

        return $notifications;
    }

    public function deleteAll($owner_id)
    {
        return $this->notificationRepository->deleteAll($owner_id);
    }

    public function markAsRead($notification_id)
    {
        $notification = $this->notificationRepository->findById($notification_id);

        if ($notification) {
            $owner_id = auth()->id();
            $read_by = $notification->read_by ?? [];

            // Check if the user's ID is not already in the 'read_by' array
            if (!in_array($owner_id, $read_by)) {
                $read_by[] = $owner_id;

                $notification->update([
                    'is_read' => true,
                    'read_at' => now(),
                    'read_by' => $read_by,
                ]);

                return $notification;
            }

            return $notification;
        }

        return null;
    }

    protected function paginateNotifications($notifications, $per_page)
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $notifications->forPage($page, $per_page);
        $paginator = new LengthAwarePaginator($items, $notifications->count(), $per_page, $page);

        return $paginator;
    }
}
