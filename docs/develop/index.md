## Events

!!! tip "Symfony Events"

    This documentation references the [Symfony EventDispatcher](http://symfony.com/components/EventDispatcher).
    If this is unfamiliar, you can read [a general introduction to Symfony events](http://symfony.com/doc/2.7/components/event_dispatcher.html)
    or [a specific introduction about CiviCRM and Symfony events](https://docs.civicrm.org/dev/en/latest/hooks/setup/symfony/).

FlexMailer is an *event* based delivery system. It defines three primary events:

* [WalkBatchesEvent](WalkBatchesEvent.md): In this event, one examines the recipient list and pulls out a subset for whom you want to send email.
* [ComposeBatchEvent](ComposeBatchEvent.md): In this event, one examines the mail content and the list of recipients -- then composes a batch of fully-formed email messages.
* [SendBatchEvent](SendBatchEvent.md): In this event, one takes a batch of fully-formed email messages and delivers the messages.

Each event supports a series of listeners.  The default listeners behave in basically the same way as CiviMail's traditional BAO-based delivery
system (respecting `mailerJobSize`, `mailThrottleTime`, `mailing_backend`, `hook_civicrm_alterMailParams`, etal).  However, you can customize
the system by adding or overriding listeners.

!!! tip "Debugging"

    When dealing with events and listeners, a fundamental question is: "What listeners are active?  When do they fire?" Answer this using the
    command `cv debug:event-dispatcher`.  For example, at time of writing, these are the default listeners:

    ```
    $ cv debug:event-dispatcher /flexmail/
    [Event] hook_civicrm_flexmailer_walk
    +-------+---------------------------------------------------+
    | Order | Callable                                          |
    +-------+---------------------------------------------------+
    | #1    | \Civi\Core\CiviEventDispatcher::delegateToUF()    |
    | #2    | Civi\FlexMailer\Listener\DefaultBatcher->onWalk() |
    +-------+---------------------------------------------------+

    [Event] hook_civicrm_flexmailer_compose
    +-------+-------------------------------------------------------+
    | Order | Callable                                              |
    +-------+-------------------------------------------------------+
    | #1    | Civi\FlexMailer\Listener\BasicHeaders->onCompose()    |
    | #2    | Civi\FlexMailer\Listener\ToHeader->onCompose()        |
    | #3    | Civi\FlexMailer\Listener\BounceTracker->onCompose()   |
    | #4    | Civi\FlexMailer\Listener\DefaultComposer->onCompose() |
    | #5    | \Civi\Core\CiviEventDispatcher::delegateToUF()        |
    | #6    | Civi\FlexMailer\Listener\Attachments->onCompose()     |
    | #7    | Civi\FlexMailer\Listener\OpenTracker->onCompose()     |
    | #8    | Civi\FlexMailer\Listener\HookAdapter->onCompose()     |
    +-------+-------------------------------------------------------+

    [Event] hook_civicrm_flexmailer_send
    +-------+--------------------------------------------------+
    | Order | Callable                                         |
    +-------+--------------------------------------------------+
    | #1    | \Civi\Core\CiviEventDispatcher::delegateToUF()   |
    | #2    | Civi\FlexMailer\Listener\DefaultSender->onSend() |
    +-------+--------------------------------------------------+
    ```

!!! note "CMS Hooks"
    It is *possible* to subscribe to these events in other ways. Note that each event has a listener named `delegateToUF()`
    which will rebroadcast the event using a Drupal hook, WordPress action, or similar. For example, in a Drupal 7
    module, one might say:

    ```php
    <?php
    function mymodule_civicrm_flexmailer_walk(WalkBatchesEvent $event) { ... }
    function mymodule_civicrm_flexmailer_compose(ComposeBatchEvent $event) { ... }
    function mymodule_civicrm_flexmailer_send(SendBatchEvent $event) { ... }
    ```

    However, it's *strongly recommended* to use Symfony `EventDispatcher` notation with FlexMailer events.  This provides more transparency (via
    `debug:event-dispatcher`), allows custom weights/priorities, and works intuitively with `stopPropagation()`.

## Unit tests

The headless unit tests are based on `phpunit4` and `cv`. Simply run:

```
$ phpunit4
```
