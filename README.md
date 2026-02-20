# modes/telega-bot

PHP-библиотека для работы с [Telegram Bot API](https://core.telegram.org/bots/api).

Поддерживает PHP >= 8.4, использует [Guzzle](https://docs.guzzlephp.org) для HTTP-запросов и [PSR-3](https://www.php-fig.org/psr/psr-3/) для логирования.

---

## Содержание

- [Установка](#установка)
- [Быстрый старт](#быстрый-старт)
- [Режимы работы](#режимы-работы)
  - [Long Polling](#long-polling)
  - [Webhook](#webhook)
- [Система обработчиков](#система-обработчиков)
  - [Обработчик команд](#обработчик-команд)
  - [Обработчик callback-запросов](#обработчик-callback-запросов)
  - [Обработчик текстовых сообщений](#обработчик-текстовых-сообщений)
  - [Fallback-обработчик](#fallback-обработчик)
  - [Ручная регистрация](#ручная-регистрация)
  - [Загрузка из директории](#загрузка-из-директории)
- [Методы API](#методы-api)
  - [sendMessage](#sendmessage)
  - [sendPhoto](#sendphoto)
  - [sendDocument](#senddocument)
  - [editMessageText](#editmessagetext)
  - [deleteMessage](#deletemessage)
  - [answerCallbackQuery](#answercallbackquery)
  - [getMe](#getme)
  - [getUpdates](#getupdates)
- [Клавиатуры](#клавиатуры)
  - [InlineKeyboardMarkup](#inlinekeyboardmarkup)
  - [ReplyKeyboardMarkup](#replykeyboardmarkup)
- [Хранилище состояния](#хранилище-состояния)
- [Логирование](#логирование)
- [Обработка ошибок](#обработка-ошибок)
- [Структура проекта](#структура-проекта)
- [Тесты](#тесты)

---

## Установка

```bash
composer require modes/telega-bot
```

---

## Быстрый старт

```php
use Modes\TelegaBot\Bot;
use Modes\TelegaBot\Dispatcher;
use Modes\TelegaBot\Handlers\AbstractCommandHandler;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\Update;

// Создаём бота
$bot = new Bot('YOUR_BOT_TOKEN');

// Создаём диспетчер
$dispatcher = new Dispatcher($bot);

// Регистрируем обработчик команды /start
$dispatcher->addHandler(new class extends AbstractCommandHandler {
    protected function getCommand(): string
    {
        return '/start';
    }

    public function handle(Update $update, Bot $bot): void
    {
        $bot->send(new SendMessage(
            chatId: $update->message->chat->id,
            text:   'Привет! Я бот. Введи /help для справки.',
        ));
    }
});

// Запускаем в режиме polling
$dispatcher->runPoling();
```

---

## Режимы работы

### Long Polling

Бот самостоятельно периодически запрашивает обновления у Telegram.
Подходит для разработки и небольших проектов.

```php
$bot        = new Bot('YOUR_BOT_TOKEN');
$dispatcher = new Dispatcher($bot, pathHandlers: __DIR__ . '/handlers');

$dispatcher->runPoling(
    limit:   100, // кол-во обновлений за один запрос
    timeout: 0,   // таймаут long polling (0 = short polling)
);
```

### Webhook

Telegram сам отправляет обновления на указанный URL.
Подходит для продакшн-окружений.

```php
// index.php — точка входа для webhook
use Modes\TelegaBot\Bot;
use Modes\TelegaBot\Dispatcher;

$bot        = new Bot('YOUR_BOT_TOKEN');
$dispatcher = new Dispatcher($bot, pathHandlers: __DIR__ . '/handlers');

$dispatcher->runHook();
```

Для регистрации webhook используйте Telegram API:

```
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.com/index.php
```

---

## Система обработчиков

Диспетчер перебирает обработчики в порядке их регистрации и вызывает
**первый**, чей метод `supports()` вернул `true`. Остальные игнорируются.

### Обработчик команд

Наследуйтесь от `AbstractCommandHandler` для обработки команд (`/start`, `/help` и т.д.).
Поддерживает команды с аргументами и команды вида `/start@MyBot` в группах.

```php
use Modes\TelegaBot\Handlers\AbstractCommandHandler;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\Update;
use Modes\TelegaBot\Bot;

class EchoCommandHandler extends AbstractCommandHandler
{
    protected function getCommand(): string
    {
        return '/echo';
    }

    public function handle(Update $update, Bot $bot): void
    {
        // Получить аргументы как строку: '/echo Hello World' → 'Hello World'
        $text = $this->getArgumentsString($update);

        $bot->send(new SendMessage(
            chatId: $update->message->chat->id,
            text:   $text ?: 'Введите текст после команды: /echo <текст>',
        ));
    }
}
```

**Доступные методы:**

| Метод | Описание |
|---|---|
| `getArguments(Update $update): string[]` | Аргументы команды в виде массива |
| `getArgumentsString(Update $update): string` | Аргументы команды одной строкой |

### Обработчик callback-запросов

Наследуйтесь от `AbstractCallbackQueryHandler` для обработки нажатий на инлайн-кнопки.

```php
use Modes\TelegaBot\Handlers\AbstractCallbackQueryHandler;
use Modes\TelegaBot\Methods\AnswerCallbackQuery;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\Update;
use Modes\TelegaBot\Bot;

// Точное совпадение callback_data
class ApproveHandler extends AbstractCallbackQueryHandler
{
    protected function getCallbackData(): string
    {
        return 'approve';
    }

    public function handle(Update $update, Bot $bot): void
    {
        $query = $update->callbackQuery;

        // Обязательно отвечаем на callback, иначе кнопка "зависнет"
        $bot->send(new AnswerCallbackQuery(
            callbackQueryId: $query->id,
            text:            'Действие подтверждено!',
        ));

        $bot->send(new SendMessage(
            chatId: $query->message->chat->id,
            text:   'Вы нажали кнопку "Подтвердить".',
        ));
    }
}
```

**Совпадение по префиксу** — удобно для передачи ID через callback_data:

```php
class ItemHandler extends AbstractCallbackQueryHandler
{
    protected function getCallbackData(): string
    {
        return 'item:'; // совпадает с 'item:1', 'item:42', 'item:999'
    }

    protected function matchByPrefix(): bool
    {
        return true;
    }

    public function handle(Update $update, Bot $bot): void
    {
        $itemId = $this->getPayload($update); // '42' из 'item:42'

        $bot->send(new AnswerCallbackQuery($update->callbackQuery->id));
        $bot->send(new SendMessage(
            chatId: $update->callbackQuery->message->chat->id,
            text:   "Вы выбрали товар #$itemId",
        ));
    }
}
```

### Обработчик текстовых сообщений

Наследуйтесь от `AbstractTextHandler` для обработки произвольных текстовых сообщений.

```php
use Modes\TelegaBot\Handlers\AbstractTextHandler;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\Update;
use Modes\TelegaBot\Bot;

// Точное совпадение
class HelpTextHandler extends AbstractTextHandler
{
    protected function getText(): string
    {
        return 'Помощь';
    }

    public function handle(Update $update, Bot $bot): void
    {
        $bot->send(new SendMessage(
            chatId: $update->message->chat->id,
            text:   'Чем могу помочь?',
        ));
    }
}

// Совпадение по регулярному выражению
class PhoneHandler extends AbstractTextHandler
{
    protected function getText(): string
    {
        return '/^\+?[0-9]{10,15}$/';
    }

    protected function matchByRegex(): bool
    {
        return true;
    }

    public function handle(Update $update, Bot $bot): void
    {
        $bot->send(new SendMessage(
            chatId: $update->message->chat->id,
            text:   'Принял номер: ' . $update->message->text,
        ));
    }
}
```

### Fallback-обработчик

Обрабатывает любое текстовое сообщение, которое не подошло другим обработчикам.
**Регистрируйте последним.**

```php
use Modes\TelegaBot\Handlers\AbstractTextHandler;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\Update;
use Modes\TelegaBot\Bot;

class FallbackHandler extends AbstractTextHandler
{
    protected function getText(): string
    {
        return '';
    }

    protected function matchAny(): bool
    {
        return true;
    }

    public function handle(Update $update, Bot $bot): void
    {
        $bot->send(new SendMessage(
            chatId: $update->message->chat->id,
            text:   'Я не понимаю эту команду. Напишите /help.',
        ));
    }
}
```

### Ручная регистрация

```php
$dispatcher
    ->addHandler(new StartHandler())
    ->addHandler(new HelpHandler())
    ->addHandler(new EchoCommandHandler())
    ->addHandler(new ApproveHandler())      // inline-кнопка 'approve'
    ->addHandler(new ItemHandler())         // inline-кнопки 'item:*'
    ->addHandler(new FallbackHandler());    // должен быть последним
```

### Загрузка из директории

Поместите файлы обработчиков в директорию. Каждый файл должен **возвращать** экземпляр `AbstractHandler`.

```
handlers/
├── StartHandler.php
├── HelpHandler.php
├── EchoHandler.php
└── FallbackHandler.php
```

```php
// handlers/StartHandler.php
use Modes\TelegaBot\Bot;
use Modes\TelegaBot\Handlers\AbstractCommandHandler;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\Update;

return new class extends AbstractCommandHandler {
    protected function getCommand(): string
    {
        return '/start';
    }

    public function handle(Update $update, Bot $bot): void
    {
        $bot->send(new SendMessage(
            chatId: $update->message->chat->id,
            text:   'Привет! Я бот.',
        ));
    }
};
```

```php
// bot.php
$dispatcher = new Dispatcher($bot, pathHandlers: __DIR__ . '/handlers');
// Файлы загружаются автоматически при создании Dispatcher

$dispatcher->runPoling();
```

Или вручную:

```php
$dispatcher = new Dispatcher($bot);
$dispatcher->loadHandlers(); // загрузка из $pathHandlers
```

---

## Методы API

Все методы реализуют `MethodsInterface` и передаются в `$bot->send()`.

### sendMessage

Отправить текстовое сообщение.

```php
use Modes\TelegaBot\Methods\SendMessage;

$bot->send(new SendMessage(
    chatId:                $chatId,
    text:                  '<b>Жирный</b> текст',
    parseMode:             'HTML',
    replyMarkup:           $keyboard,
    replyToMessageId:      $messageId,
    disableWebPagePreview: true,
    disableNotification:   false,
));
```

### sendPhoto

Отправить фото по file_id, URL или пути к файлу.

```php
use Modes\TelegaBot\Methods\SendPhoto;

$bot->send(new SendPhoto(
    chatId:  $chatId,
    photo:   'https://example.com/photo.jpg', // или file_id
    caption: 'Подпись к фото',
    parseMode: 'HTML',
));
```

### sendDocument

Отправить документ.

```php
use Modes\TelegaBot\Methods\SendDocument;

$bot->send(new SendDocument(
    chatId:   $chatId,
    document: 'BQACAgIAAxkB...', // file_id
    caption:  'Важный файл',
));
```

### editMessageText

Редактировать текст отправленного сообщения.

```php
use Modes\TelegaBot\Methods\EditMessageText;

$bot->send(new EditMessageText(
    text:      'Новый текст сообщения',
    chatId:    $chatId,
    messageId: $messageId,
    parseMode: 'HTML',
));
```

### deleteMessage

Удалить сообщение из чата.

```php
use Modes\TelegaBot\Methods\DeleteMessage;

$bot->send(new DeleteMessage(
    chatId:    $chatId,
    messageId: $messageId,
));
```

### answerCallbackQuery

Ответить на callback-запрос от инлайн-кнопки.
Необходимо вызывать при каждом нажатии инлайн-кнопки.

```php
use Modes\TelegaBot\Methods\AnswerCallbackQuery;

// Просто убрать "загрузку" с кнопки
$bot->send(new AnswerCallbackQuery(callbackQueryId: $query->id));

// Показать toast-уведомление
$bot->send(new AnswerCallbackQuery(
    callbackQueryId: $query->id,
    text:            'Готово!',
));

// Показать alert-диалог
$bot->send(new AnswerCallbackQuery(
    callbackQueryId: $query->id,
    text:            'Вы уверены?',
    showAlert:       true,
));
```

### getMe

Получить информацию о боте.

```php
use Modes\TelegaBot\Methods\GetMe;

$response = $bot->send(new GetMe());
// $response->result — массив с данными бота (id, username, first_name, ...)
```

### getUpdates

Использовать напрямую обычно не нужно — `Dispatcher::runPoling()` делает это автоматически.

```php
use Modes\TelegaBot\Methods\GetUpdates;
use Modes\TelegaBot\Storage\FileStorage;

$storage  = new FileStorage('/tmp/bot_state.json');
$response = $bot->send(new GetUpdates($storage, limit: 50, timeout: 30));

foreach ($response->result as $update) {
    // $update — экземпляр Update
}
```

---

## Клавиатуры

### InlineKeyboardMarkup

Инлайн-кнопки прикрепляются к сообщению.

```php
use Modes\TelegaBot\Types\InlineKeyboardButton;
use Modes\TelegaBot\Types\InlineKeyboardMarkup;

// Одна строка кнопок
$keyboard = InlineKeyboardMarkup::singleRow(
    InlineKeyboardButton::callbackButton('Да',  'answer:yes'),
    InlineKeyboardButton::callbackButton('Нет', 'answer:no'),
);

// Кнопки столбцом
$keyboard = InlineKeyboardMarkup::column(
    InlineKeyboardButton::callbackButton('Пункт 1', 'item:1'),
    InlineKeyboardButton::callbackButton('Пункт 2', 'item:2'),
    InlineKeyboardButton::callbackButton('Пункт 3', 'item:3'),
);

// Произвольная сетка (строки × колонки)
$keyboard = new InlineKeyboardMarkup([
    [
        InlineKeyboardButton::callbackButton('1', 'btn:1'),
        InlineKeyboardButton::callbackButton('2', 'btn:2'),
    ],
    [
        InlineKeyboardButton::urlButton('Открыть сайт', 'https://example.com'),
    ],
]);

$bot->send(new SendMessage(
    chatId:      $chatId,
    text:        'Выберите вариант:',
    replyMarkup: $keyboard,
));
```

### ReplyKeyboardMarkup

Кнопки отображаются вместо стандартной клавиатуры устройства.

```php
use Modes\TelegaBot\Types\KeyboardButton;
use Modes\TelegaBot\Types\ReplyKeyboardMarkup;

// Одна строка кнопок
$keyboard = ReplyKeyboardMarkup::singleRow(
    KeyboardButton::make('Да'),
    KeyboardButton::make('Нет'),
);

// Кнопки столбцом
$keyboard = ReplyKeyboardMarkup::column(
    KeyboardButton::make('Меню'),
    KeyboardButton::make('Настройки'),
    KeyboardButton::make('Помощь'),
);

// Одноразовая клавиатура (скрывается после нажатия)
$keyboard = ReplyKeyboardMarkup::oneTime([
    [KeyboardButton::make('Подтвердить'), KeyboardButton::make('Отмена')],
]);

// Кнопка запроса контакта
$keyboard = ReplyKeyboardMarkup::singleRow(
    KeyboardButton::requestContact('Поделиться контактом'),
);

// Кнопка запроса геолокации
$keyboard = ReplyKeyboardMarkup::singleRow(
    KeyboardButton::requestLocation('Отправить местоположение'),
);

$bot->send(new SendMessage(
    chatId:      $chatId,
    text:        'Выберите действие:',
    replyMarkup: $keyboard,
));
```

---

## Хранилище состояния

Хранилище используется для сохранения ID последнего обновления между запросами при polling.

По умолчанию используется `FileStorage` (`/tmp/telega_bot_storage.json`).

### Своя реализация

Реализуйте `StorageInterface` для использования Redis, базы данных и т.д.:

```php
use Modes\TelegaBot\Contracts\StorageInterface;

class RedisStorage implements StorageInterface
{
    public function __construct(private \Redis $redis) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->redis->set($key, serialize($value));
    }

    public function has(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function clear(): void
    {
        $this->redis->flushDB();
    }
}
```

Передайте хранилище в `Dispatcher`:

```php
$storage    = new RedisStorage(new Redis());
$dispatcher = new Dispatcher($bot, storage: $storage);
```

---

## Логирование

Библиотека поддерживает PSR-3 совместимые логгеры (Monolog и другие).
По умолчанию используется `NullLogger` — все сообщения отбрасываются.

```php
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Modes\TelegaBot\Bot;
use Modes\TelegaBot\Dispatcher;

$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler('php://stdout'));

$bot        = new Bot('YOUR_BOT_TOKEN', logger: $logger);
$dispatcher = new Dispatcher($bot, logger: $logger);

$dispatcher->runPoling();
```

---

## Обработка ошибок

### Исключения

| Класс | Описание |
|---|---|
| `TelegaBotException` | Базовое исключение библиотеки |
| `ApiException` | Telegram API вернул `ok: false` |
| `HandlerNotFoundException` | Ни один обработчик не подошёл (если включено) |

```php
use Modes\TelegaBot\Exceptions\ApiException;
use Modes\TelegaBot\Exceptions\TelegaBotException;

try {
    $bot->send(new SendMessage(chatId: 0, text: 'test'));
} catch (ApiException $e) {
    echo "Ошибка API [{$e->getErrorCode()}]: {$e->getMessage()}";
} catch (TelegaBotException $e) {
    echo "Ошибка библиотеки: {$e->getMessage()}";
}
```

### Поведение при ошибках в Dispatcher

По умолчанию `Dispatcher` **не бросает** исключение, если ни один обработчик не подошёл (сообщение просто игнорируется). Чтобы изменить это поведение:

```php
$dispatcher = new Dispatcher(
    bot: $bot,
    throwOnMissedHandler: true, // бросать HandlerNotFoundException
);
```

Ошибки внутри обработчиков всегда перехватываются и логируются,
чтобы один сломанный обработчик не остановил бота.

---

## Структура проекта

```
src/
├── Bot.php                             # Основной класс, выполняет HTTP-запросы
├── BotResponse.php                     # Обёртка ответа API (ok + result)
├── Dispatcher.php                      # Маршрутизация обновлений, polling, webhook
│
├── Contracts/
│   ├── MethodsInterface.php            # Контракт для методов API
│   ├── MarkupInterface.php             # Контракт для типов клавиатур
│   └── StorageInterface.php            # Контракт для хранилища состояния
│
├── Exceptions/
│   ├── TelegaBotException.php          # Базовое исключение
│   ├── ApiException.php                # Ошибки Telegram API
│   └── HandlerNotFoundException.php   # Обработчик не найден
│
├── Handlers/
│   ├── AbstractHandler.php             # Базовый класс обработчика
│   ├── AbstractCommandHandler.php      # Обработчик команд (/start, /help...)
│   ├── AbstractCallbackQueryHandler.php# Обработчик callback-кнопок
│   └── AbstractTextHandler.php        # Обработчик текстовых сообщений
│
├── Methods/
│   ├── GetUpdates.php                  # Получение обновлений (polling)
│   ├── SendMessage.php                 # Отправка текстового сообщения
│   ├── SendPhoto.php                   # Отправка фото
│   ├── SendDocument.php                # Отправка документа
│   ├── EditMessageText.php             # Редактирование текста
│   ├── DeleteMessage.php               # Удаление сообщения
│   ├── AnswerCallbackQuery.php         # Ответ на callback-запрос
│   └── GetMe.php                       # Информация о боте
│
├── Storage/
│   └── FileStorage.php                 # Файловое хранилище состояния (JSON)
│
└── Types/
    ├── Update.php                      # Входящее обновление
    ├── Message.php                     # Сообщение
    ├── Chat.php                        # Чат
    ├── User.php                        # Пользователь
    ├── CallbackQuery.php               # Callback-запрос от инлайн-кнопки
    ├── PhotoSize.php                   # Размер фото
    ├── Document.php                    # Документ
    ├── InlineKeyboardMarkup.php        # Инлайн-клавиатура
    ├── InlineKeyboardButton.php        # Кнопка инлайн-клавиатуры
    ├── ReplyKeyboardMarkup.php         # Reply-клавиатура
    └── KeyboardButton.php              # Кнопка reply-клавиатуры
```

---

## Тесты

```bash
# Установить зависимости (включая dev)
composer install

# Запустить все тесты
./vendor/bin/phpunit

# Запустить с отчётом о покрытии (требует Xdebug или PCOV)
./vendor/bin/phpunit --coverage-text
```
```

Now let me install the dependencies and run the tests: