<?php

/*
 * This file is part of Wepps Platform.
 *
 * (c) 2026 PETROFFS LLC (ООО «ПЕТРОФФС»)
 * Registered with Rospatent (Russian Federation): certificate No. 2026694013 of 03.11.2026.
 *
 * SPDX-License-Identifier: LicenseRef-Wepps-EULA
 *
 * Licensed under the Wepps Platform EULA: https://github.com/petroffs/wepps/blob/master/LICENSE
 * Free for non-commercial use (Community). Commercial use requires
 * a written license agreement with PETROFFS LLC.
 * This notice must not be removed or altered (EULA, sec. 2.2).
 *
 * @author Aleksei Petrov <mail@petroffs.com>
 */

namespace WeppsExtensions\Addons\Webhooks;

use WeppsCore\Connect;
use WeppsCore\Utils;

class Webhooks
{
    private $post;
    private $get;
    private $token;
    private $input;
    public function __construct($get = [], $post = [])
    {
        $this->post = $post;
        $this->get = $get;
        $this->token = $this->get['token'] ?? '';
        $this->input = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->handleEvent($this->get['action'] ?? '', $this->input);
        http_response_code($result['status']);
        header('Content-Type: application/json');
        echo Utils::json($result);
    }

    public function handleEvent(string $action, array &$data): array
    {
        // Обработка вебхуков в зависимости от типа события
        switch ($action) {
            case 'gitlab.webhook':
                return $this->updateFromGitlabWebhook($data);
            case 'git.webhook':
                return $this->updateFromGitWebhook($data);
            default:
                return [
                    'status' => 400,
                    'message' => 'Unknown event type',
                    'data' => null
                ];
        }
    }

    private function updateFromGitlabWebhook(array $data): array
    {
        if (empty(Connect::$projectServices['wepps']['git']) || Connect::$projectServices['wepps']['git'] != $_SERVER['HTTP_X_GITLAB_TOKEN']) {
            return [
                'status' => 401,
                'message' => 'Unauthorized',
                'data' => null
            ];
        }
        $dir = Connect::$projectDev['root'];
        $git = "{$dir}/.git";
        $message = trim($data['commits'][0]['message']);
        $branch = "main";
        if (strstr($message, "dev:")) {
            $branch = "dev";
        }
        if (preg_match('/prod:|dev:|chore:|fix:|feat:|refactor:|test:/i', $message)) {
            $cmd = "git --work-tree={$dir} --git-dir={$git} fetch origin {$branch} && git --work-tree={$dir} --git-dir={$git} reset --hard origin/{$branch}";
            #$cmd = "cd {$dir} && git pull origin {$branch}";
            exec($cmd);
        }

        // Логика обработки события создания заказа
        return [
            'status' => 200,
            'message' => 'Order created handled successfully',
            'data' => $data
        ];
    }

    private function updateFromGitWebhook(array $data): array
    {
        $token = Connect::$projectServices['wepps']['git'];
        if (empty($token) || $token != $_SERVER['HTTP_CLIENTTOKEN']) {
            return [
                'status' => 401,
                'message' => 'Unauthorized',
                'data' => null
            ];
        }
        $dir = Connect::$projectDev['root'];
        $git = "{$dir}/.git";
        $branch = 'main';
        $cmd = "git --work-tree={$dir} --git-dir={$git} fetch origin {$branch} && git --work-tree={$dir} --git-dir={$git} reset --hard origin/{$branch}";
        exec($cmd);
        return [
            'status' => 200,
            'message' => 'Product updated handled successfully',
            'data' => $data
        ];
    }
}