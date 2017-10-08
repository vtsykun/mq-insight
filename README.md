# Message queue insight bundle

This bundle provide UI to monitor status and collect statistics of message queue for OroPlatform

## Requirements

* This package requires Unix/Linux (Window supported partly) with `ps` utils

## Non-mandatory requirements

* Extension `ext-amqp`
* Support `ext-sysvmsg`

## Support

* Amqp or dbal driver


## Install

```
composer require okvpn/mq-insight dev-master
```

After install run command 
```
php app/console oro:platform:update --force
```

## Demo

http://demo.oroinc.me/insight/queue-status/ (login: admin, password: admin)

## Contributing 

Contributions from the community are accepted. 

## Licences

MIT Licences 
