<?php namespace App\Mercury\Services;

use App\Mercury\Helpers\StatusHelper;
use Vinkla\Pusher\Facades\Pusher;

class PusherService
{
    protected $channel;

    public function __construct()
    {
        $this->channel = 'my-channel';
    }

    public function trigger($event, $data)
    {
        $channel = $this->channel;

        $pusher = Pusher::trigger($channel, $event, $data);

        return $pusher;
    }




    public function triggerRefreshSaleEvent()
    {
        $this->trigger(StatusHelper::EVENT_REFRESH_SALE, []);
    }

    public function triggerNotification()
    {
        $this->trigger(StatusHelper::EVENT_UPDATE_NOTIFICATION, []);
    }

    public function triggerNotificationToast($message, $title = 'NOTIFICATION', $type = 'info')
    {
        if(!$message){
            return false;
        }

        if(trim($message) == ""){
            return false;
        }

        $this->trigger(StatusHelper::EVENT_NOTIFICATION_TOAST, [
            'message' => $message,
            'title' => $title,
            'type' => $type
        ]);
    }

}