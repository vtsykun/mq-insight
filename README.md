# Message queue insight bundle

This bundle provide UI to monitor status and collect statistics of message queue for OroPlatform

[![Latest Stable Version](https://poser.pugx.org/okvpn/mq-insight/version)](https://packagist.org/packages/okvpn/mq-insight) [![Latest Unstable Version](https://poser.pugx.org/okvpn/mq-insight/v/unstable)](//packagist.org/packages/okvpn/mq-insight) [![Total Downloads](https://poser.pugx.org/okvpn/mq-insight/downloads)](https://packagist.org/packages/okvpn/mq-insight) [![License](https://poser.pugx.org/okvpn/mq-insight/license)](https://packagist.org/packages/okvpn/mq-insight)

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

## Contributing 

Contributions from the community are accepted. 

## Licences

MIT Licences 
