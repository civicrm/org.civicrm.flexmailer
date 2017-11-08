## Events

!!! tip "Symfony Events"

    This documentation references the [Symfony EventDispatcher](http://symfony.com/components/EventDispatcher).
    If this is unfamiliar, you can read [a general introduction to Symfony events](http://symfony.com/doc/2.7/components/event_dispatcher.html)
    or [a specific introduction about CiviCRM and Symfony events](https://docs.civicrm.org/dev/en/latest/hooks/setup/symfony/).

FlexMailer is an *event* based delivery system. It defines three primary events:

* [WalkBatchesEvent](/develop/WalkBatchesEvent.md): In this event, one examines the recipient list and pulls out a subset for whom you want to send email.
* [ComposeBatchEvent](/develop/ComposeBatchEvent.md): In this event, one examines the mail content and the list of recipients -- then composes a batch of fully-formed email messages.
* [SendBatchEvent](/develop/SendBatchEvent.md): In this event, one takes a batch of fully-formed email messages and delivers the messages.

Each event supports a series of listeners.  The default listeners behave in basically the same way as CiviMail's traditional BAO-based delivery
system (respecting `mailerJobSize`, `mailThrottleTime`, `mailing_backend`, `hook_civicrm_alterMailParams`, etal).  However, you can customize
the system by adding or overriding listeners.

!!! tip "Debugging"

    When dealing with events and listeners, a fundamental question is: "What listeners are active?  When do they fire?" Answer this using the
    command `cv debug:event-dispatcher`.  For example, at time of writing, these are the default listeners:

    ```
    $ cv debug:event-dispatcher /flexmail/
    [Event] civi.flexmailer.walk
    +-------+---------------------------------------------------+
    | Order | Callable                                          |
    +-------+---------------------------------------------------+
    | #1    | Civi\FlexMailer\Listener\DefaultBatcher->onWalk() |
    +-------+---------------------------------------------------+

    [Event] civi.flexmailer.compose
    +-------+-------------------------------------------------------+
    | Order | Callable                                              |
    +-------+-------------------------------------------------------+
    | #1    | Civi\FlexMailer\Listener\BasicHeaders->onCompose()    |
    | #2    | Civi\FlexMailer\Listener\ToHeader->onCompose()        |
    | #3    | Civi\FlexMailer\Listener\BounceTracker->onCompose()   |
    | #4    | Civi\FlexMailer\Listener\DefaultComposer->onCompose() |
    | #5    | Civi\FlexMailer\Listener\Attachments->onCompose()     |
    | #6    | Civi\FlexMailer\Listener\OpenTracker->onCompose()     |
    | #7    | Civi\FlexMailer\Listener\HookAdapter->onCompose()     |
    +-------+-------------------------------------------------------+

    [Event] civi.flexmailer.send
    +-------+--------------------------------------------------+
    | Order | Callable                                         |
    +-------+--------------------------------------------------+
    | #1    | Civi\FlexMailer\Listener\DefaultSender->onSend() |
    +-------+--------------------------------------------------+
    ```


## Unit tests

The headless unit tests are based on `phpunit4` and `cv`. Simply run:

```
$ phpunit4
```
