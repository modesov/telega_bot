<?php

declare(strict_types=1);

namespace Modes\TelegaBot\Tests\Unit\Methods;

use Modes\TelegaBot\BotResponse;
use Modes\TelegaBot\Contracts\MarkupInterface;
use Modes\TelegaBot\Methods\SendMessage;
use Modes\TelegaBot\Types\InlineKeyboardButton;
use Modes\TelegaBot\Types\InlineKeyboardMarkup;
use Modes\TelegaBot\Types\KeyboardButton;
use Modes\TelegaBot\Types\ReplyKeyboardMarkup;
use PHPUnit\Framework\TestCase;

final class SendMessageTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getMethod
    // -------------------------------------------------------------------------

    public function test_get_method_returns_correct_api_method_name(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');

        $this->assertSame('sendMessage', $method->getMethod());
    }

    // -------------------------------------------------------------------------
    // getRequestParams — required fields
    // -------------------------------------------------------------------------

    public function test_get_request_params_contains_chat_id_and_text(): void
    {
        $method = new SendMessage(chatId: 42, text: 'Test message');
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('chat_id', $params);
        $this->assertArrayHasKey('text', $params);
        $this->assertSame(42, $params['chat_id']);
        $this->assertSame('Test message', $params['text']);
    }

    public function test_get_request_params_accepts_string_chat_id(): void
    {
        $method = new SendMessage(chatId: '@mychannel', text: 'Hello channel');
        $params = $method->getRequestParams();

        $this->assertSame('@mychannel', $params['chat_id']);
    }

    public function test_get_request_params_accepts_integer_chat_id(): void
    {
        $method = new SendMessage(chatId: -100123456789, text: 'Hello group');
        $params = $method->getRequestParams();

        $this->assertSame(-100123456789, $params['chat_id']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — optional fields absent by default
    // -------------------------------------------------------------------------

    public function test_get_request_params_does_not_include_parse_mode_by_default(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');
        $params = $method->getRequestParams();

        $this->assertArrayNotHasKey('parse_mode', $params);
    }

    public function test_get_request_params_does_not_include_reply_markup_by_default(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');
        $params = $method->getRequestParams();

        $this->assertArrayNotHasKey('reply_markup', $params);
    }

    public function test_get_request_params_does_not_include_reply_to_message_id_by_default(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');
        $params = $method->getRequestParams();

        $this->assertArrayNotHasKey('reply_to_message_id', $params);
    }

    public function test_get_request_params_does_not_include_disable_web_page_preview_by_default(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');
        $params = $method->getRequestParams();

        $this->assertArrayNotHasKey('disable_web_page_preview', $params);
    }

    public function test_get_request_params_does_not_include_disable_notification_by_default(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');
        $params = $method->getRequestParams();

        $this->assertArrayNotHasKey('disable_notification', $params);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — parse_mode
    // -------------------------------------------------------------------------

    public function test_get_request_params_includes_parse_mode_html(): void
    {
        $method = new SendMessage(chatId: 1, text: '<b>Bold</b>', parseMode: 'HTML');
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('parse_mode', $params);
        $this->assertSame('HTML', $params['parse_mode']);
    }

    public function test_get_request_params_includes_parse_mode_markdown(): void
    {
        $method = new SendMessage(chatId: 1, text: '*Bold*', parseMode: 'Markdown');
        $params = $method->getRequestParams();

        $this->assertSame('Markdown', $params['parse_mode']);
    }

    public function test_get_request_params_includes_parse_mode_markdown_v2(): void
    {
        $method = new SendMessage(chatId: 1, text: '*Bold*', parseMode: 'MarkdownV2');
        $params = $method->getRequestParams();

        $this->assertSame('MarkdownV2', $params['parse_mode']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — reply_to_message_id
    // -------------------------------------------------------------------------

    public function test_get_request_params_includes_reply_to_message_id(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Reply!', replyToMessageId: 99);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('reply_to_message_id', $params);
        $this->assertSame(99, $params['reply_to_message_id']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — disable_web_page_preview
    // -------------------------------------------------------------------------

    public function test_get_request_params_includes_disable_web_page_preview_true(): void
    {
        $method = new SendMessage(chatId: 1, text: 'https://example.com', disableWebPagePreview: true);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('disable_web_page_preview', $params);
        $this->assertTrue($params['disable_web_page_preview']);
    }

    public function test_get_request_params_includes_disable_web_page_preview_false(): void
    {
        $method = new SendMessage(chatId: 1, text: 'https://example.com', disableWebPagePreview: false);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('disable_web_page_preview', $params);
        $this->assertFalse($params['disable_web_page_preview']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — disable_notification
    // -------------------------------------------------------------------------

    public function test_get_request_params_includes_disable_notification_true(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Silent message', disableNotification: true);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('disable_notification', $params);
        $this->assertTrue($params['disable_notification']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — reply_markup (inline keyboard)
    // -------------------------------------------------------------------------

    public function test_get_request_params_includes_inline_keyboard_markup(): void
    {
        $keyboard = InlineKeyboardMarkup::singleRow(
            InlineKeyboardButton::callbackButton('Click me', 'action_click'),
        );

        $method = new SendMessage(chatId: 1, text: 'Choose:', replyMarkup: $keyboard);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('reply_markup', $params);
        $this->assertArrayHasKey('inline_keyboard', $params['reply_markup']);

        $rows = $params['reply_markup']['inline_keyboard'];
        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows[0]);
        $this->assertSame('Click me', $rows[0][0]['text']);
        $this->assertSame('action_click', $rows[0][0]['callback_data']);
    }

    public function test_get_request_params_includes_inline_keyboard_with_url_button(): void
    {
        $keyboard = InlineKeyboardMarkup::singleRow(
            InlineKeyboardButton::urlButton('Visit', 'https://example.com'),
        );

        $method = new SendMessage(chatId: 1, text: 'Link:', replyMarkup: $keyboard);
        $params = $method->getRequestParams();

        $row = $params['reply_markup']['inline_keyboard'][0];
        $this->assertSame('Visit', $row[0]['text']);
        $this->assertSame('https://example.com', $row[0]['url']);
    }

    public function test_get_request_params_includes_column_inline_keyboard(): void
    {
        $keyboard = InlineKeyboardMarkup::column(
            InlineKeyboardButton::callbackButton('Option A', 'option_a'),
            InlineKeyboardButton::callbackButton('Option B', 'option_b'),
            InlineKeyboardButton::callbackButton('Option C', 'option_c'),
        );

        $method = new SendMessage(chatId: 1, text: 'Pick one:', replyMarkup: $keyboard);
        $params = $method->getRequestParams();

        $rows = $params['reply_markup']['inline_keyboard'];
        $this->assertCount(3, $rows);
        $this->assertSame('Option A', $rows[0][0]['text']);
        $this->assertSame('Option B', $rows[1][0]['text']);
        $this->assertSame('Option C', $rows[2][0]['text']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — reply_markup (reply keyboard)
    // -------------------------------------------------------------------------

    public function test_get_request_params_includes_reply_keyboard_markup(): void
    {
        $keyboard = ReplyKeyboardMarkup::singleRow(
            KeyboardButton::make('Button 1'),
            KeyboardButton::make('Button 2'),
        );

        $method = new SendMessage(chatId: 1, text: 'Choose:', replyMarkup: $keyboard);
        $params = $method->getRequestParams();

        $this->assertArrayHasKey('reply_markup', $params);
        $this->assertArrayHasKey('keyboard', $params['reply_markup']);

        $rows = $params['reply_markup']['keyboard'];
        $this->assertCount(1, $rows);
        $this->assertCount(2, $rows[0]);
        $this->assertSame('Button 1', $rows[0][0]['text']);
        $this->assertSame('Button 2', $rows[0][1]['text']);
    }

    public function test_get_request_params_includes_one_time_reply_keyboard(): void
    {
        $keyboard = ReplyKeyboardMarkup::oneTime([
            [KeyboardButton::make('Yes'), KeyboardButton::make('No')],
        ]);

        $method = new SendMessage(chatId: 1, text: 'Confirm?', replyMarkup: $keyboard);
        $params = $method->getRequestParams();

        $markup = $params['reply_markup'];
        $this->assertTrue($markup['one_time_keyboard']);
        $this->assertSame('Yes', $markup['keyboard'][0][0]['text']);
        $this->assertSame('No', $markup['keyboard'][0][1]['text']);
    }

    // -------------------------------------------------------------------------
    // getRequestParams — custom MarkupInterface implementation
    // -------------------------------------------------------------------------

    public function test_get_request_params_calls_to_array_on_markup(): void
    {
        $markup = new class implements MarkupInterface {
            public function toArray(): array
            {
                return ['custom_markup' => true];
            }
        };

        $method = new SendMessage(chatId: 1, text: 'Custom markup', replyMarkup: $markup);
        $params = $method->getRequestParams();

        $this->assertSame(['custom_markup' => true], $params['reply_markup']);
    }

    // -------------------------------------------------------------------------
    // getResponse
    // -------------------------------------------------------------------------

    public function test_get_response_returns_bot_response_with_ok_true(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');

        $response = $method->getResponse([
            'ok'     => true,
            'result' => ['message_id' => 123],
        ]);

        $this->assertInstanceOf(BotResponse::class, $response);
        $this->assertTrue($response->ok);
        $this->assertSame(['message_id' => 123], $response->result);
    }

    public function test_get_response_returns_bot_response_with_ok_false(): void
    {
        $method = new SendMessage(chatId: 1, text: 'Hello');

        $response = $method->getResponse([
            'ok'          => false,
            'error_code'  => 400,
            'description' => 'Bad Request',
        ]);

        $this->assertInstanceOf(BotResponse::class, $response);
        $this->assertFalse($response->ok);
        $this->assertNull($response->result);
    }

    public function test_get_response_returns_null_result_when_result_key_missing(): void
    {
        $method   = new SendMessage(chatId: 1, text: 'Hello');
        $response = $method->getResponse(['ok' => true]);

        $this->assertTrue($response->ok);
        $this->assertNull($response->result);
    }

    // -------------------------------------------------------------------------
    // Combined parameters
    // -------------------------------------------------------------------------

    public function test_all_optional_parameters_included_together(): void
    {
        $keyboard = InlineKeyboardMarkup::singleRow(
            InlineKeyboardButton::callbackButton('OK', 'ok'),
        );

        $method = new SendMessage(
            chatId: 100,
            text: 'Full message',
            parseMode: 'HTML',
            replyMarkup: $keyboard,
            replyToMessageId: 50,
            disableWebPagePreview: true,
            disableNotification: true,
        );

        $params = $method->getRequestParams();

        $this->assertSame(100, $params['chat_id']);
        $this->assertSame('Full message', $params['text']);
        $this->assertSame('HTML', $params['parse_mode']);
        $this->assertSame(50, $params['reply_to_message_id']);
        $this->assertTrue($params['disable_web_page_preview']);
        $this->assertTrue($params['disable_notification']);
        $this->assertArrayHasKey('reply_markup', $params);
    }
}
