<?php

declare(strict_types=1);

namespace Modes\TelegaBot;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modes\TelegaBot\Contracts\MethodsInterface;
use Modes\TelegaBot\Exceptions\ApiException;
use Modes\TelegaBot\Exceptions\TelegaBotException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Основной класс Telegram-бота.
 *
 * Отвечает за инициализацию HTTP-клиента и отправку запросов
 * к Telegram Bot API через объекты, реализующие {@see MethodsInterface}.
 */
final class Bot
{
    /**
     * HTTP-клиент для взаимодействия с Telegram Bot API.
     */
    private Client $httpClient;

    /**
     * PSR-3 логгер.
     */
    private LoggerInterface $logger;

    /**
     * Создаёт экземпляр бота и инициализирует HTTP-клиент.
     *
     * @param string               $token  Токен Telegram-бота, полученный от @BotFather.
     * @param LoggerInterface|null $logger PSR-3 совместимый логгер. Если не передан — используется NullLogger.
     */
    public function __construct(
        private readonly string $token,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();

        $this->httpClient = new Client([
            "base_uri" => "https://api.telegram.org/bot" . $this->token . "/",
            "timeout" => 30,
        ]);
    }

    /**
     * Отправляет запрос к Telegram Bot API с использованием переданного метода.
     *
     * Сериализует параметры запроса, выполняет HTTP-запрос и десериализует ответ
     * через метод {@see MethodsInterface::getResponse()}.
     *
     * @param MethodsInterface $method Объект метода API, содержащий название метода,
     *                                  параметры запроса и логику разбора ответа.
     *
     * @return BotResponse|null Объект ответа от API или null в случае сетевой ошибки.
     *
     * @throws ApiException        Если Telegram API вернул ok: false.
     * @throws TelegaBotException  Если произошла ошибка при обработке ответа.
     */
    public function send(MethodsInterface $method): ?BotResponse
    {
        $apiMethod = $method->getMethod();
        $params = $method->getRequestParams();

        $this->logger->debug("Sending request to Telegram API", [
            "method" => $apiMethod,
            "params" => $params,
        ]);

        try {
            $responseBody = $this->sendRequest($apiMethod, ["json" => $params])
                ->getBody()
                ->getContents();

            $data = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

            if (!$data["ok"]) {
                $exception = ApiException::fromResponse($data);

                $this->logger->error("Telegram API returned an error", [
                    "method" => $apiMethod,
                    "error_code" => $data["error_code"] ?? null,
                    "description" => $data["description"] ?? null,
                ]);

                throw $exception;
            }

            $response = $method->getResponse($data);

            $this->logger->debug(
                "Received successful response from Telegram API",
                [
                    "method" => $apiMethod,
                ],
            );

            return $response;
        } catch (ApiException $e) {
            throw $e;
        } catch (GuzzleException $e) {
            $this->logger->error("HTTP error while calling Telegram API", [
                "method" => $apiMethod,
                "message" => $e->getMessage(),
            ]);

            return null;
        } catch (\JsonException $e) {
            $this->logger->error("Failed to decode Telegram API response", [
                "method" => $apiMethod,
                "message" => $e->getMessage(),
            ]);

            throw new TelegaBotException(
                message: "Failed to decode Telegram API response: " .
                    $e->getMessage(),
                previous: $e,
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unexpected error while calling Telegram API",
                [
                    "method" => $apiMethod,
                    "message" => $e->getMessage(),
                ],
            );

            throw new TelegaBotException(
                message: "Unexpected error: " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * Выполняет POST-запрос к указанному методу Telegram Bot API.
     *
     * @param string               $apiMethod   Название метода API (например, 'sendMessage').
     * @param array<string, mixed> $requestBody Тело запроса в формате Guzzle.
     *
     * @return ResponseInterface PSR-совместимый объект HTTP-ответа.
     *
     * @throws GuzzleException Если произошла ошибка при выполнении HTTP-запроса.
     */
    private function sendRequest(
        string $apiMethod,
        array $requestBody,
    ): ResponseInterface {
        return $this->httpClient->post($apiMethod, $requestBody);
    }
}
