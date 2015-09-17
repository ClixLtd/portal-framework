<?php namespace Portal\Integrations\Slack\Classes;

use Illuminate\Support\Facades\Config;
use Maknz\Slack\Attachment;
use Maknz\Slack\AttachmentField;
use Maknz\Slack\Client;
use MySecurePortal\OldPortal\Domain\Users\Models\User;
use Portal\Notifications\Contracts\Notification;

class SlackNotification implements Notification {

    protected $slack;

    public function send($message, array $settings = [], User $user = null)
    {
        // TODO: Implement send() method.
    }

    public static function attachment(array $settings)
    {
        return new Attachment($settings);
    }

    public static function attachmentField(array $settings)
    {
        return new AttachmentField($settings);
    }

    public static function create()
    {
        $thisClass = new static();

        return $thisClass->slack;
    }

    public function __construct()
    {
        $this->slack = new Client(Config::get('slack.endpoint'));
    }

}
