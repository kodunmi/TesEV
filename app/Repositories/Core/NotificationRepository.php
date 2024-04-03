<?php

namespace App\Repositories\Core;


use App\Models\Notification;

class NotificationRepository
{
    public function all()
    {
        return Notification::all();
    }

    public function findById($id)
    {
        if (!isUuid($id)) {
            return null;
        }

        return Notification::find($id);
    }

    public function create(array $data)
    {
        $notification = new Notification();
        $notification->user_id = $data['user_id'] ?? null;
        $notification->title = $data['title'] ?? null;
        $notification->body = $data['body'] ?? null;
        $notification->preview = $data['preview'] ?? null;
        $notification->channel = $data['channel'] ?? null;
        $notification->url = $data['url'] ?? null;
        $notification->is_read = $data['is_read'] ?? false;
        $notification->show = $data['show'] ?? true;
        $notification->read_by = $data['read_by'] ?? null;
        $notification->read_at = $data['read_at'] ?? null;
        $notification->sent_by = $data['sent_by'] ?? null;
        $notification->type = $data['type'] ?? null;
        $notification->markup_body = $data['markup_body'] ?? null;
        $notification->meta = $data['meta'] ?? null;
        $notification->data = $data['data'] ?? null;
        $notification->attachments = $data['attachments'] ?? null;
        $notification->public_id = uuid();

        $notification->save();

        return $notification;
    }

    public function update($id, array $data)
    {
        $notification = $this->findById($id);
        if ($notification) {
            $notification->update($data);

            return $notification;
        }

        return null;
    }

    public function delete($id)
    {
        $notification = $this->findById($id);
        if ($notification) {
            return $notification->delete();
        }

        return false;
    }

    public function getAllNotifications($user_id, $per_page = 10)
    {
        $notifications = Notification::where('user_id', $user_id)->where('show', true)->paginate($per_page);

        return $notifications;
    }

    public function notificationCounts($user_id)
    {
        $notifications = Notification::where('user_id', $user_id)->where('show', true)->count();
        $unread_count = Notification::where('user_id', $user_id)->where('show', true)->where('is_read', false)->count();
        $read_count = Notification::where('user_id', $user_id)->where('show', true)->where('is_read', true)->count();

        return [
            'all_count' => $notifications,
            'unread_count' => $unread_count,
            'read_count' => $read_count,
        ];
    }

    public function markAllAsRead($user_id)
    {
        return Notification::where('user_id', $user_id)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function deleteAll($user_id)
    {
        return Notification::where('user_id', $user_id)->delete();
    }
}
