# Lib for smsc.ua

This package provides a set of reusable validation rules for your Laravel projects. Use them to augment the existing set provided by Laravel itself.

## Installation

Pull in the package using composer

```bash
composer require prodanv/smsc
```

## Usage

```php
use ProdanV\SMSc\SMSc;
use ProdanV\SMSc\Message;

$smsc = new SMSc('login', 'password'); // If you need logging you can use third param to pass logger

$message = new Message('Hello world =)');
$message->from('Me')->to('+380123456789'); // Also can use array

$smsc->send($message); // Also can use array of Message

```

## Contributions

You are welcome to submit pull requests containing your own validation rules, however to be accepted, they must explain what they do, be useful to others, and include a suitable test to confirm they work correctly.