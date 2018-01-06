<?php namespace App\DTIStore\Services;

use App\DTIStore\Helpers\StatusHelper;
use Vinkla\Pusher\Facades\Pusher;

class PusherService
{
    protected $channel;

    public function __construct()
    {
        $this->channel = 'dti-store-channel';
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