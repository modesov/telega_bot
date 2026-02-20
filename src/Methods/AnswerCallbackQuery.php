<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MethodsInterface;

/**
 * Метод answerCallbackQuery — ответ на входящий callback-запрос от инлайн-кнопки.
 *
 * Необходимо вызывать после получения CallbackQuery, иначе кнопка будет
 * отображаться в состоянии загрузки до 5 минут.
 *
 * @see https://core.telegram.org/bots/api#answercallbackquery
 */
readonly class AnswerCallbackQuery implements MethodsInterface
{
    /**
     * @param string      $callbackQueryId Уникальный идентификатор callback-запроса.
     * @param string|null $text            Текст уведомления (0–200 символов).
     *                                     Если не задан — пользователь не увидит уведомления.
     * @param bool|null   $showAlert       Если true — показывает alert вместо toast-уведомления.
     * @param string|null $url             URL, который будет открыт клиентом.
     * @param int|null    $cacheTime       Максимальное время (в секундах) кеширования результата на стороне клиента.
     */
    public function __construct(
        private string $callbackQueryId,
        private ?string $text = null,
        private ?bool $showAlert = null,
        private ?string $url = null,
        private ?int $cacheTime = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return "answerCallbackQuery";
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParams(): array
    {
        $params = [
            "callback_query_id" => $this->callbackQueryId,
        ];

        if ($this->text !== null) {
            $params["text"] = $this->text;
        }

        if ($this->showAlert !== null) {
            $params["show_alert"] = $this->showAlert;
        }

        if ($this->url !== null) {
            $params["url"] = $this->url;
        }

        if ($this->cacheTime !== null) {
            $params["cache_time"] = $this->cacheTime;
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     *
     * При успехе Telegram возвращает True (булево значение).
     */
    public function getResponse(array $data): BotResponse
    {
        return new BotResponse(
            ok: $data["ok"],
            result: $data["result"] ?? null,
        );
    }
}
