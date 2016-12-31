# org.civicrm.flexmailer

The FlexMailer is a email delivery system for CiviCRM v4.7+ which supports batching and events.

FlexMailer emits five Symfony events:

  * [`WalkBatchesEvent`](src/Event/WalkBatchesEvent.php) (`EVENT_WALK`): Examine the recipient list and pull out a subset for whom you want to send email.
  * [`ComposeBatchEvent`](src/Event/ComposeBatchEvent.php) (`EVENT_COMPOSE`): Given a batch of recipients, prepare an email message for each.
  * [`AlterBatchEvent`](src/Event/AlterBatchEvent.php) (`EVENT_ALTER`): Given a batch of recipients and their messages, change the content of the messages.
  * [`SendBatchEvent`](src/Event/SendBatchEvent.php) (`EVENT_SEND`): Given a batch of recipients and their  messages, send the messages out.
  * [`RunEvent`](src/Event/RunEvent.php) (`EVENT_RUN`): Execute the main-loop (with all of the above steps).

FlexMailer includes default listeners for all of these events.  They behave
in basically the same way as CiviMail's traditional BAO-based delivery
system (respecting `mailerJobSize`, `mailThrottleTime`, `mailing_backend`,
`hook_civicrm_alterMailParams`, etal).  However, you can replace any of the
major functions, e.g.

 * If you send large blasts across multiple servers, then you may prefer a different algorithm for splitting the recipient list.
   Listen for `WalkBatchesEvent`.
 * If you want to compose messages in a new way (e.g. a different templating language), then listen for `ComposeBatchEvent`.
 * If you want to add extra email headers or tracking codes, then listen for `AlterBatchEvent`.
 * If you want to deliver messages through a different medium (such as web-services or batched SMTP), listen for `SendBatchEvent`.

In all cases, your function can listen to the event and then decide what to
do.  If your listener does the work required for the event, then disable the
default listener by calling `$event->stopPropagation()`.

## Unit Tests

The headless unit tests are based on `phpunit` and `cv`. Simply run:

```
phpunit4
```

## FAQ: How do you register a listener?

You may register event listeners with `hook_civicrm_container`, e.g.

```php
function example_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  // Use addListener, addListenerService, addSubscriber, or addSubscriberService.
  $container->findDefinition('dispatcher')->addMethodCall('addListener', 
    array(\Civi\FlexMailer\FlexMailer::EVENT_ALTER, '_example_alter_batch')
  );
}

function _example_alter_batch(\Civi\FlexMailer\Event\AlterBatchEvent $event) {
  // ...
}
```

This example uses `addListener()` to register a single global function; however, you
can also add multievent "subscribers" via `addSubscriber()`, and you can register
object-oriented services via `addListenerService()` or `addSubscriberService()`.

For more discussion, see http://symfony.com/doc/current/components/event_dispatcher/introduction.html

## FAQ: Why use Symfony `EventDispatcher` instead of `CRM_Utils_Hook`?

There are several advantages of Symfony's event system -- for example, it
supports type-hinting, better in-source documentation, better
object-oriented modeling, and better refactoring.  However, that's not why
FlexMailer uses it.  Two characteristics are particularly relevant to
FlexMailer -- when you want to have multiple parties influencing an event,
the EventDispatcher allows you to both (a) set priorities among them and (b)
allow one listener to veto other listeners.

Some of these characteristics are also available in CMS event systems, but
not consistently so.  The challenge of `CRM_Utils_Hook` is that it must
support the lowest-common denominator of functionality.
