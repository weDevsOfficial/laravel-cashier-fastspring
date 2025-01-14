<?php

namespace TwentyTwoDigital\CashierFastspring\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use TwentyTwoDigital\CashierFastspring\Events;
use TwentyTwoDigital\CashierFastspring\Tests\Fixtures\WebhookControllerTestStub;

class WebhookControllerTest extends TestCase
{
    /**
     * Class constructor.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__ . '/.env')) {
            $dotenv = \Dotenv\Dotenv::create(__DIR__);
            $dotenv->load();
        }
    }

    /**
     * Test HMAC.
     *
     * @return \Illuminate\Http\Response
     */
    public function testHmac()
    {
        $hmacSecret = 'dontlookiamsecret';
        Config::set('services.fastspring.hmac_secret', $hmacSecret);

        $webhookRequestPayload = [
            'events' => [
                [
                    'id'        => 'id-1',
                    'live'      => true,
                    'processed' => false,
                    'type'      => 'account.created',
                    'created'   => 1426560444800,
                    'data'      => [],
                ],
            ],
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $request->headers->set(
            'X-FS-Signature',
            base64_encode(hash_hmac('sha256', $request->getContent(), $hmacSecret, true))
        );

        $controller = new WebhookControllerTestStub();
        $response = $controller->handleWebhook($request);

        $this->assertEquals($response->getStatusCode(), 202);
    }

    /**
     * Test HMAC failed.
     *
     * @throws \Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function testHmacFailed()
    {
        Config::set('services.fastspring.hmac_secret', 'dontlookiamsecret');
        $this->expectException(\Exception::class);

        $webhookRequestPayload = [
            'events' => [
                [
                    'id'        => 'id-1',
                    'live'      => true,
                    'processed' => false,
                    'type'      => 'account.created',
                    'created'   => 1426560444800,
                    'data'      => [],
                ],
            ],
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub();
        $response = $controller->handleWebhook($request);
    }

    public function testMultipleWebhookEvents()
    {
        $webhookRequestPayload = [
            'events' => [
                [
                    'id'        => 'id-1',
                    'live'      => true,
                    'processed' => false,
                    'type'      => 'account.created',
                    'created'   => 1426560444800,
                    'data'      => [],
                ],
                [
                    'id'        => 'id-2',
                    'live'      => true,
                    'processed' => false,
                    'type'      => 'subscription.activated',
                    'created'   => 1426560444800,
                    'data'      => [],
                ],
            ],
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub();
        $response = $controller->handleWebhook($request);

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        $this->assertEquals($statusCode, 202);
        $this->assertEquals($content, "id-1\nid-2");
    }

    /**
     * Test multiple webhook events by failing one.
     *
     * @return void
     */
    public function testMultipleWebhookEventsByFailingOne()
    {
        $webhookRequestPayload = [
            'events' => [
                [
                    'id'        => 'id-1',
                    'live'      => true,
                    'processed' => false,
                    'type'      => 'account.created',
                    'created'   => 1426560444800,
                    'data'      => [],
                ],
                [
                    'id'        => 'id-2',
                    'live'      => true,
                    'processed' => false,
                    'type'      => 'subscription.notexistevent',
                    'created'   => 1426560444800,
                    'data'      => [],
                ],
            ],
        ];

        // since the second event doesn't exist
        // there will be error and we only see first one handled
        // alson in the content of the response

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub();
        $response = $controller->handleWebhook($request);

        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        $this->assertEquals($statusCode, 202);
        $this->assertEquals($content, 'id-1');
    }

    /**
     * Webhook test events.
     *
     * @return \TwentyTwoDigital\CashierFastspring\Tests\WebhookControllerTest\sendRequestAndListenEvents
     */
    public function testWebhooksEvents()
    {
        $webhookEvents = [
            'account.created',
            'fulfillment.failed',
            'mailingListEntry.removed',
            'mailingListEntry.updated',
            'order.approval.pending',
            'order.canceled',
            'order.payment.pending',
            'order.completed',
            'order.failed',
            'payoutEntry.created',
            'return.created',
            'subscription.activated',
            'subscription.canceled',
            'subscription.charge.completed',
            'subscription.charge.failed',
            'subscription.deactivated',
            'subscription.payment.overdue',
            'subscription.payment.reminder',
            'subscription.trial.reminder',
            'subscription.updated',
        ];

        foreach ($webhookEvents as $key => $webhookEvent) {
            $mockEvent = [
                'id'        => 'id-' . $key,
                'live'      => true,
                'processed' => false,
                'type'      => $webhookEvent,
                'created'   => 1426560444800,
                'data'      => [],
            ];

            // prepare category event class names like OrderAny
            $explodedType = explode('.', $mockEvent['type']);
            $category = array_shift($explodedType);
            $categoryEvent = 'TwentyTwoDigital\CashierFastspring\Events\\' . Str::studly($category) . 'Any';

            // prepare category event class names like activity
            $activity = str_replace('.', ' ', $mockEvent['type']);
            $activityEvent = 'TwentyTwoDigital\CashierFastspring\Events\\' . Str::studly($activity);

            $listenEvents = [
                Events\Any::class,
                $categoryEvent,
                $activityEvent,
            ];

            $this->sendRequestAndListenEvents($mockEvent, $listenEvents);
        }
    }

    /**
     * Sends request and listen for events.
     *
     * @param array $mockEvent    The mock event array
     * @param array $listenEvents The listen events
     *
     * @return \Illuminate\Support\Facades\Event
     */
    protected function sendRequestAndListenEvents($mockEvent, $listenEvents)
    {
        Event::fake();

        $webhookRequestPayload = [
            'events' => [
                $mockEvent,
            ],
        ];

        $request = Request::create('/', 'POST', [], [], [], [], json_encode($webhookRequestPayload));
        $controller = new WebhookControllerTestStub();
        $controller->handleWebhook($request);

        foreach ($listenEvents as $listenEvent) {
            // Assert
            Event::assertDispatched(
                $listenEvent,
                function ($event) use ($mockEvent) {
                    return (int) $event->id === (int) $mockEvent['id'];
                }
            );
        }
    }
}
