<?php

namespace App\Listeners;

use App\Actions\Notifications\NotificationService;
use App\Enum\ChargeTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Repositories\Core\CardRepository;
use App\Repositories\User\UserRepository;
use Laravel\Cashier\Events\WebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StripeEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected CardRepository $cardRepository
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] === 'invoice.payment_succeeded') {

            if ($event->payload['data']['object']['billing_reason'] == 'subscription_create') {

                $customer = $event->payload['data']['object']['customer'];

                $user = $this->userRepository->findByStripeId($customer);

                $total =  $event->payload['data']['object']['total'];

                if ($user) {
                    $this->userRepository->updateUser($user->id, [
                        'subscription_balance' => $user->subscription_balance + $total
                    ]);
                }
            }
        }

        if ($event->payload['type'] === 'customer.source.deleted') {

            if (isset($event->payload['data']['object']['id'])) {

                $card_id = $event->payload['data']['object']['id'];


                $card = $this->cardRepository->findByStripeId($card_id);


                if ($card) {
                    $this->cardRepository->delete($card->id);
                }
            }
        }


        if ($event->payload['type'] === 'payment_intent.succeeded') {

            $charge_type = $event->payload['data']['object']['metadata']['type'];

            $user_id = $event->payload['data']['object']['metadata']['user_id'];

            $amount = $event->payload['data']['object']['amount'];

            $user = $this->userRepository->findById($user_id);

            if ($charge_type === ChargeTypeEnum::WALLET_FUND->value) {

                if ($user) {
                    $updated = $this->userRepository->updateUser($user->id, [
                        'wallet' => $user->wallet + $amount
                    ]);

                    if ($updated) {

                        $notification = new NotificationService($user);

                        $notification
                            ->setBody("Transaction successful, your wallet has been created with " . centToDollar($amount))
                            ->setTitle('Your wallet has been credited')
                            ->setUrl('http://google.com')
                            ->setType(NotificationTypeEnum::WALLET_FUND)
                            ->sendPushNotification()
                            ->sendInAppNotification();
                    }
                }
            }
        }
    }
}
