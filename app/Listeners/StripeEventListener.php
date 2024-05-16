<?php

namespace App\Listeners;

use App\Actions\Notifications\NotificationService;
use App\Enum\ChargeTypeEnum;
use App\Enum\NotificationTypeEnum;
use App\Enum\PaymentTypeEnum;
use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypeEnum;
use App\Enum\TripStatusEnum;
use App\Models\TripTransaction;
use App\Models\WalletTransaction;
use App\Repositories\Core\CardRepository;
use App\Repositories\Core\TransactionRepository;
use App\Repositories\Core\TripRepository;
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
        protected CardRepository $cardRepository,
        protected TripRepository $tripRepository,
        protected TransactionRepository $transactionRepository
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


        if ($event->payload['type'] === 'payment_intent.payment_failed') {
            $charge_type = $event->payload['data']['object']['metadata']['type'];
            $amount = $event->payload['data']['object']['amount'];


            if ($charge_type === ChargeTypeEnum::WALLET_FUND->value) {
                try {
                    $user_id = $event->payload['data']['object']['metadata']['user_id'];
                    $wallet_transaction_id = $event->payload['data']['object']['metadata']['wallet_transaction_id'];


                    $wallet_transaction = WalletTransaction::findOrFail($wallet_transaction_id);

                    $wallet_transaction->update([
                        'status' => TransactionStatusEnum::FAILED->value,
                    ]);

                    $wallet_transaction->transaction()->update([
                        'status' => TransactionStatusEnum::FAILED->value,
                    ]);

                    $user = $this->userRepository->findById($user_id);

                    if ($user) {

                        $notification = new NotificationService($user);

                        $notification
                            ->setBody("Transaction failed, we could not fund your wallet with " . centToDollar($amount))
                            ->setTitle('Transaction')
                            ->setUrl('http://google.com')
                            ->setType(NotificationTypeEnum::WALLET_FUND_FAILED)
                            ->sendPushNotification()
                            ->sendInAppNotification();
                    }
                } catch (\Throwable $th) {
                    logError("Error occurred @ wallet funding failed webhook", [
                        'error' => $th
                    ]);
                }
            }

            if ($charge_type === ChargeTypeEnum::TRIP_FUND->value) {

                try {
                    $trip_id = $event->payload['data']['object']['metadata']['trip_id'];

                    $trip = $this->tripRepository->findById($trip_id);

                    if ($trip) {

                        $trip->update([
                            'status' => TripStatusEnum::CANCELED->value
                        ]);

                        $trip_transaction = TripTransaction::where('trip_id', $trip->id)->first();

                        if ($trip_transaction) {
                            $trip_transaction->update([
                                'status' => TransactionStatusEnum::FAILED->value
                            ]);


                            foreach ($trip_transaction->transactions as $key => $transaction) {
                                $transaction->update([
                                    'status' => TransactionStatusEnum::FAILED->value
                                ]);
                            }
                        }


                        $notification = new NotificationService($user);

                        $notification
                            ->setBody("Transaction failed, your trip has canceled")
                            ->setTitle('Trip canceled')
                            ->setUrl('http://google.com')
                            ->setType(NotificationTypeEnum::WALLET_FUND)
                            ->sendPushNotification()
                            ->sendInAppNotification();
                    }
                } catch (\Throwable $th) {
                    logError("Error occurred @ Trip funding failed webhook", [
                        'error' => $th
                    ]);
                }
            }
        }


        if ($event->payload['type'] === 'payment_intent.succeeded') {

            $charge_type = $event->payload['data']['object']['metadata']['type'];
            $amount = $event->payload['data']['object']['amount'];


            if ($charge_type === ChargeTypeEnum::WALLET_FUND->value) {

                try {

                    $user_id = $event->payload['data']['object']['metadata']['user_id'];

                    $wallet_transaction_id = $event->payload['data']['object']['metadata']['wallet_transaction_id'];


                    $wallet_transaction = WalletTransaction::findOrFail($wallet_transaction_id);

                    $wallet_transaction->update([
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                    ]);

                    $wallet_transaction->transaction()->update([
                        'status' => TransactionStatusEnum::SUCCESSFUL->value,
                    ]);

                    $user = $this->userRepository->findById($user_id);

                    if ($user) {
                        $updated = $this->userRepository->updateUser($user->id, [
                            'wallet' => $user->wallet + $amount
                        ]);

                        $this->transactionRepository->create(
                            [
                                'amount' => $amount,
                                'user_id' => $user->id,
                                'total_amount' => $amount,
                                'title' => "Wallet token received",
                                'narration' => "Your account has been created with $amount wallet token",
                                'status' => TransactionStatusEnum::SUCCESSFUL->value,
                                'type' => TransactionTypeEnum::WALLET->value,
                                'entry' => "credit",
                                'channel' => PaymentTypeEnum::CARD->value,
                                'tax_amount' => 0.00,
                                'tax_percentage' => 0.00
                            ]
                        );

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
                } catch (\Throwable $th) {
                    logError("Error occurred @ wallet funding successful webhook", [
                        'error' => $th
                    ]);
                }
            }

            if ($charge_type === ChargeTypeEnum::TRIP_FUND->value) {

                try {
                    $trip_id = $event->payload['data']['object']['metadata']['trip_id'];

                    $trip = $this->tripRepository->findById($trip_id);

                    if ($trip) {

                        $user = $this->userRepository->findById($trip->user_id);

                        $user->update([
                            'subscription_balance' => 0
                        ]);

                        $trip->update([
                            'status' => TripStatusEnum::RESERVED->value
                        ]);

                        $trip_transaction = TripTransaction::where('trip_id', $trip->id)->first();

                        if ($trip_transaction) {
                            $trip_transaction->update([
                                'status' => TransactionStatusEnum::SUCCESSFUL->value
                            ]);


                            foreach ($trip_transaction->transactions as $key => $transaction) {
                                $transaction->update([
                                    'status' => TransactionStatusEnum::SUCCESSFUL->value
                                ]);
                            }
                        }


                        $notification = new NotificationService($user);

                        $notification
                            ->setBody("Transaction successful, your trip has reserved")
                            ->setTitle('Trip reserved')
                            ->setUrl('http://google.com')
                            ->setType(NotificationTypeEnum::WALLET_FUND)
                            ->sendPushNotification()
                            ->sendInAppNotification();
                    }
                } catch (\Throwable $th) {
                    logError("Error occurred @ Trip funding successful webhook", [
                        'error' => $th
                    ]);
                }
            }
        }
    }
}



// Handle the event
// switch ($event->type) {
//   case 'account.updated':
//     $account = $event->data->object;
//   case 'account.external_account.created':
//     $externalAccount = $event->data->object;
//   case 'account.external_account.deleted':
//     $externalAccount = $event->data->object;
//   case 'account.external_account.updated':
//     $externalAccount = $event->data->object;
//   case 'balance.available':
//     $balance = $event->data->object;
//   case 'billing_portal.configuration.created':
//     $configuration = $event->data->object;
//   case 'billing_portal.configuration.updated':
//     $configuration = $event->data->object;
//   case 'billing_portal.session.created':
//     $session = $event->data->object;
//   case 'capability.updated':
//     $capability = $event->data->object;
//   case 'cash_balance.funds_available':
//     $cashBalance = $event->data->object;
//   case 'charge.captured':
//     $charge = $event->data->object;
//   case 'charge.expired':
//     $charge = $event->data->object;
//   case 'charge.failed':
//     $charge = $event->data->object;
//   case 'charge.pending':
//     $charge = $event->data->object;
//   case 'charge.refunded':
//     $charge = $event->data->object;
//   case 'charge.succeeded':
//     $charge = $event->data->object;
//   case 'charge.updated':
//     $charge = $event->data->object;
//   case 'charge.dispute.closed':
//     $dispute = $event->data->object;
//   case 'charge.dispute.created':
//     $dispute = $event->data->object;
//   case 'charge.dispute.funds_reinstated':
//     $dispute = $event->data->object;
//   case 'charge.dispute.funds_withdrawn':
//     $dispute = $event->data->object;
//   case 'charge.dispute.updated':
//     $dispute = $event->data->object;
//   case 'charge.refund.updated':
//     $refund = $event->data->object;
//   case 'checkout.session.async_payment_failed':
//     $session = $event->data->object;
//   case 'checkout.session.async_payment_succeeded':
//     $session = $event->data->object;
//   case 'checkout.session.completed':
//     $session = $event->data->object;
//   case 'checkout.session.expired':
//     $session = $event->data->object;
//   case 'climate.order.canceled':
//     $order = $event->data->object;
//   case 'climate.order.created':
//     $order = $event->data->object;
//   case 'climate.order.delayed':
//     $order = $event->data->object;
//   case 'climate.order.delivered':
//     $order = $event->data->object;
//   case 'climate.order.product_substituted':
//     $order = $event->data->object;
//   case 'climate.product.created':
//     $product = $event->data->object;
//   case 'climate.product.pricing_updated':
//     $product = $event->data->object;
//   case 'coupon.created':
//     $coupon = $event->data->object;
//   case 'coupon.deleted':
//     $coupon = $event->data->object;
//   case 'coupon.updated':
//     $coupon = $event->data->object;
//   case 'credit_note.created':
//     $creditNote = $event->data->object;
//   case 'credit_note.updated':
//     $creditNote = $event->data->object;
//   case 'credit_note.voided':
//     $creditNote = $event->data->object;
//   case 'customer.created':
//     $customer = $event->data->object;
//   case 'customer.deleted':
//     $customer = $event->data->object;
//   case 'customer.updated':
//     $customer = $event->data->object;
//   case 'customer.discount.created':
//     $discount = $event->data->object;
//   case 'customer.discount.deleted':
//     $discount = $event->data->object;
//   case 'customer.discount.updated':
//     $discount = $event->data->object;
//   case 'customer.source.created':
//     $source = $event->data->object;
//   case 'customer.source.deleted':
//     $source = $event->data->object;
//   case 'customer.source.expiring':
//     $source = $event->data->object;
//   case 'customer.source.updated':
//     $source = $event->data->object;
//   case 'customer.subscription.created':
//     $subscription = $event->data->object;
//   case 'customer.subscription.deleted':
//     $subscription = $event->data->object;
//   case 'customer.subscription.paused':
//     $subscription = $event->data->object;
//   case 'customer.subscription.pending_update_applied':
//     $subscription = $event->data->object;
//   case 'customer.subscription.pending_update_expired':
//     $subscription = $event->data->object;
//   case 'customer.subscription.resumed':
//     $subscription = $event->data->object;
//   case 'customer.subscription.trial_will_end':
//     $subscription = $event->data->object;
//   case 'customer.subscription.updated':
//     $subscription = $event->data->object;
//   case 'customer.tax_id.created':
//     $taxId = $event->data->object;
//   case 'customer.tax_id.deleted':
//     $taxId = $event->data->object;
//   case 'customer.tax_id.updated':
//     $taxId = $event->data->object;
//   case 'customer_cash_balance_transaction.created':
//     $customerCashBalanceTransaction = $event->data->object;
//   case 'entitlements.active_entitlement_summary.updated':
//     $activeEntitlementSummary = $event->data->object;
//   case 'file.created':
//     $file = $event->data->object;
//   case 'financial_connections.account.created':
//     $account = $event->data->object;
//   case 'financial_connections.account.deactivated':
//     $account = $event->data->object;
//   case 'financial_connections.account.disconnected':
//     $account = $event->data->object;
//   case 'financial_connections.account.reactivated':
//     $account = $event->data->object;
//   case 'financial_connections.account.refreshed_balance':
//     $account = $event->data->object;
//   case 'financial_connections.account.refreshed_ownership':
//     $account = $event->data->object;
//   case 'financial_connections.account.refreshed_transactions':
//     $account = $event->data->object;
//   case 'identity.verification_session.canceled':
//     $verificationSession = $event->data->object;
//   case 'identity.verification_session.created':
//     $verificationSession = $event->data->object;
//   case 'identity.verification_session.processing':
//     $verificationSession = $event->data->object;
//   case 'identity.verification_session.requires_input':
//     $verificationSession = $event->data->object;
//   case 'identity.verification_session.verified':
//     $verificationSession = $event->data->object;
//   case 'invoice.created':
//     $invoice = $event->data->object;
//   case 'invoice.deleted':
//     $invoice = $event->data->object;
//   case 'invoice.finalization_failed':
//     $invoice = $event->data->object;
//   case 'invoice.finalized':
//     $invoice = $event->data->object;
//   case 'invoice.marked_uncollectible':
//     $invoice = $event->data->object;
//   case 'invoice.overdue':
//     $invoice = $event->data->object;
//   case 'invoice.paid':
//     $invoice = $event->data->object;
//   case 'invoice.payment_action_required':
//     $invoice = $event->data->object;
//   case 'invoice.payment_failed':
//     $invoice = $event->data->object;
//   case 'invoice.payment_succeeded':
//     $invoice = $event->data->object;
//   case 'invoice.sent':
//     $invoice = $event->data->object;
//   case 'invoice.upcoming':
//     $invoice = $event->data->object;
//   case 'invoice.updated':
//     $invoice = $event->data->object;
//   case 'invoice.voided':
//     $invoice = $event->data->object;
//   case 'invoice.will_be_due':
//     $invoice = $event->data->object;
//   case 'invoiceitem.created':
//     $invoiceitem = $event->data->object;
//   case 'invoiceitem.deleted':
//     $invoiceitem = $event->data->object;
//   case 'issuing_authorization.created':
//     $issuingAuthorization = $event->data->object;
//   case 'issuing_authorization.updated':
//     $issuingAuthorization = $event->data->object;
//   case 'issuing_card.created':
//     $issuingCard = $event->data->object;
//   case 'issuing_card.updated':
//     $issuingCard = $event->data->object;
//   case 'issuing_cardholder.created':
//     $issuingCardholder = $event->data->object;
//   case 'issuing_cardholder.updated':
//     $issuingCardholder = $event->data->object;
//   case 'issuing_dispute.closed':
//     $issuingDispute = $event->data->object;
//   case 'issuing_dispute.created':
//     $issuingDispute = $event->data->object;
//   case 'issuing_dispute.funds_reinstated':
//     $issuingDispute = $event->data->object;
//   case 'issuing_dispute.submitted':
//     $issuingDispute = $event->data->object;
//   case 'issuing_dispute.updated':
//     $issuingDispute = $event->data->object;
//   case 'issuing_token.created':
//     $issuingToken = $event->data->object;
//   case 'issuing_token.updated':
//     $issuingToken = $event->data->object;
//   case 'issuing_transaction.created':
//     $issuingTransaction = $event->data->object;
//   case 'issuing_transaction.updated':
//     $issuingTransaction = $event->data->object;
//   case 'mandate.updated':
//     $mandate = $event->data->object;
//   case 'payment_intent.amount_capturable_updated':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.canceled':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.created':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.partially_funded':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.payment_failed':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.processing':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.requires_action':
//     $paymentIntent = $event->data->object;
//   case 'payment_intent.succeeded':
//     $paymentIntent = $event->data->object;
//   case 'payment_link.created':
//     $paymentLink = $event->data->object;
//   case 'payment_link.updated':
//     $paymentLink = $event->data->object;
//   case 'payment_method.attached':
//     $paymentMethod = $event->data->object;
//   case 'payment_method.automatically_updated':
//     $paymentMethod = $event->data->object;
//   case 'payment_method.detached':
//     $paymentMethod = $event->data->object;
//   case 'payment_method.updated':
//     $paymentMethod = $event->data->object;
//   case 'payout.canceled':
//     $payout = $event->data->object;
//   case 'payout.created':
//     $payout = $event->data->object;
//   case 'payout.failed':
//     $payout = $event->data->object;
//   case 'payout.paid':
//     $payout = $event->data->object;
//   case 'payout.reconciliation_completed':
//     $payout = $event->data->object;
//   case 'payout.updated':
//     $payout = $event->data->object;
//   case 'person.created':
//     $person = $event->data->object;
//   case 'person.deleted':
//     $person = $event->data->object;
//   case 'person.updated':
//     $person = $event->data->object;
//   case 'plan.created':
//     $plan = $event->data->object;
//   case 'plan.deleted':
//     $plan = $event->data->object;
//   case 'plan.updated':
//     $plan = $event->data->object;
//   case 'price.created':
//     $price = $event->data->object;
//   case 'price.deleted':
//     $price = $event->data->object;
//   case 'price.updated':
//     $price = $event->data->object;
//   case 'product.created':
//     $product = $event->data->object;
//   case 'product.deleted':
//     $product = $event->data->object;
//   case 'product.updated':
//     $product = $event->data->object;
//   case 'promotion_code.created':
//     $promotionCode = $event->data->object;
//   case 'promotion_code.updated':
//     $promotionCode = $event->data->object;
//   case 'quote.accepted':
//     $quote = $event->data->object;
//   case 'quote.canceled':
//     $quote = $event->data->object;
//   case 'quote.created':
//     $quote = $event->data->object;
//   case 'quote.finalized':
//     $quote = $event->data->object;
//   case 'quote.will_expire':
//     $quote = $event->data->object;
//   case 'radar.early_fraud_warning.created':
//     $earlyFraudWarning = $event->data->object;
//   case 'radar.early_fraud_warning.updated':
//     $earlyFraudWarning = $event->data->object;
//   case 'refund.created':
//     $refund = $event->data->object;
//   case 'refund.updated':
//     $refund = $event->data->object;
//   case 'reporting.report_run.failed':
//     $reportRun = $event->data->object;
//   case 'reporting.report_run.succeeded':
//     $reportRun = $event->data->object;
//   case 'review.closed':
//     $review = $event->data->object;
//   case 'review.opened':
//     $review = $event->data->object;
//   case 'setup_intent.canceled':
//     $setupIntent = $event->data->object;
//   case 'setup_intent.created':
//     $setupIntent = $event->data->object;
//   case 'setup_intent.requires_action':
//     $setupIntent = $event->data->object;
//   case 'setup_intent.setup_failed':
//     $setupIntent = $event->data->object;
//   case 'setup_intent.succeeded':
//     $setupIntent = $event->data->object;
//   case 'sigma.scheduled_query_run.created':
//     $scheduledQueryRun = $event->data->object;
//   case 'source.canceled':
//     $source = $event->data->object;
//   case 'source.chargeable':
//     $source = $event->data->object;
//   case 'source.failed':
//     $source = $event->data->object;
//   case 'source.mandate_notification':
//     $source = $event->data->object;
//   case 'source.refund_attributes_required':
//     $source = $event->data->object;
//   case 'source.transaction.created':
//     $transaction = $event->data->object;
//   case 'source.transaction.updated':
//     $transaction = $event->data->object;
//   case 'subscription_schedule.aborted':
//     $subscriptionSchedule = $event->data->object;
//   case 'subscription_schedule.canceled':
//     $subscriptionSchedule = $event->data->object;
//   case 'subscription_schedule.completed':
//     $subscriptionSchedule = $event->data->object;
//   case 'subscription_schedule.created':
//     $subscriptionSchedule = $event->data->object;
//   case 'subscription_schedule.expiring':
//     $subscriptionSchedule = $event->data->object;
//   case 'subscription_schedule.released':
//     $subscriptionSchedule = $event->data->object;
//   case 'subscription_schedule.updated':
//     $subscriptionSchedule = $event->data->object;
//   case 'tax.settings.updated':
//     $settings = $event->data->object;
//   case 'tax_rate.created':
//     $taxRate = $event->data->object;
//   case 'tax_rate.updated':
//     $taxRate = $event->data->object;
//   case 'terminal.reader.action_failed':
//     $reader = $event->data->object;
//   case 'terminal.reader.action_succeeded':
//     $reader = $event->data->object;
//   case 'test_helpers.test_clock.advancing':
//     $testClock = $event->data->object;
//   case 'test_helpers.test_clock.created':
//     $testClock = $event->data->object;
//   case 'test_helpers.test_clock.deleted':
//     $testClock = $event->data->object;
//   case 'test_helpers.test_clock.internal_failure':
//     $testClock = $event->data->object;
//   case 'test_helpers.test_clock.ready':
//     $testClock = $event->data->object;
//   case 'topup.canceled':
//     $topup = $event->data->object;
//   case 'topup.created':
//     $topup = $event->data->object;
//   case 'topup.failed':
//     $topup = $event->data->object;
//   case 'topup.reversed':
//     $topup = $event->data->object;
//   case 'topup.succeeded':
//     $topup = $event->data->object;
//   case 'transfer.created':
//     $transfer = $event->data->object;
//   case 'transfer.reversed':
//     $transfer = $event->data->object;
//   case 'transfer.updated':
//     $transfer = $event->data->object;
//   // ... handle other event types
//   default:
//     echo 'Received unknown event type ' . $event->type;
// }

// http_response_code(200);
