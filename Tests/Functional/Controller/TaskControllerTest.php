<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\AutomationBundle\Entity\Task;
use Sulu\Bundle\AutomationBundle\Tests\Handler\FirstHandler;
use Sulu\Bundle\AutomationBundle\Tests\Handler\SecondHandler;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Tests for task-api.
 */
class TaskControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
    }

    public function testCGet()
    {
        $postData = [
            $this->testPost(),
            $this->testPost(),
            $this->testPost(),
        ];

        $this->client->request('GET', '/api/tasks?fields=id,schedule,handlerClass,taskName');
        $this->assertHttpStatusCode(200, $this->client->getResponse(), 1000);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(3, $responseData['total']);
        $this->assertCount(3, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($postData); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $postData[$i]['id'],
                    'handlerClass' => $postData[$i]['handlerClass'],
                    'schedule' => $postData[$i]['schedule'],
                    'taskName' => $postData[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithIds()
    {
        $postData = [
            $this->testPost(),
            $this->testPost(),
            $this->testPost(),
        ];

        $ids = [$postData[2]['id'], $postData[0]['id']];

        $this->client->request('GET', '/api/tasks?ids=' . implode(',', $ids));
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($ids); $i < $length; ++$i) {
            $this->assertEquals($ids[$i], $embedded[$i]['id']);
        }
    }

    public function testCGetWithLocales()
    {
        $postData = [
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1, 'de'),
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1, 'en'),
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1, 'de'),
        ];

        $this->client->request('GET', '/api/tasks?locale=de&fields=id,schedule,handlerClass,taskName');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithEntity()
    {
        $postData = [
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1),
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 2),
            $this->testPost(FirstHandler::class, '+1 day', 'OtherClass', 1),
        ];

        $this->client->request('GET', '/api/tasks?entityClass=ThisClass&entityId=1');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['total']);
        $this->assertCount(1, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        $this->assertEquals($postData[0]['id'], $embedded[0]['id']);
    }

    public function testCGetWithFutureSchedule()
    {
        $postData = [
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1),
            $this->testPost(FirstHandler::class, '-1 day', 'ThisClass', 2),
            $this->testPost(FirstHandler::class, '+1 day', 'OtherClass', 1),
        ];

        $this->client->request('GET', '/api/tasks?fields=id,schedule,handlerClass,taskName&schedule=future');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithPastSchedule()
    {
        $postData = [
            $this->testPost(FirstHandler::class, '-1 day', 'ThisClass', 1),
            $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 2),
            $this->testPost(FirstHandler::class, '-1 day', 'OtherClass', 1),
        ];

        $this->client->request('GET', '/api/tasks?fields=id,schedule,handlerClass,taskName&schedule=past');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $items[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testCGetWithHandlerClass()
    {
        $postData = [
            $this->testPost(FirstHandler::class),
            $this->testPost(SecondHandler::class),
            $this->testPost(FirstHandler::class),
        ];

        $this->client->request(
            'GET',
            '/api/tasks?fields=id,schedule,handlerClass,taskName&handlerClass=' . FirstHandler::class
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['_embedded']['tasks']);

        $items = [$postData[0], $postData[2]];
        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($items); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $items[$i]['id'],
                    'schedule' => $items[$i]['schedule'],
                    'handlerClass' => $items[$i]['handlerClass'],
                    'taskName' => $items[$i]['taskName'],
                    'status' => $items[$i]['status'],
                ],
                $embedded
            );
        }

        $this->client->request(
            'GET',
            '/api/tasks?fields=id,schedule,handlerClass,taskName&handlerClass='
            . FirstHandler::class
            . ','
            . SecondHandler::class
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(3, $responseData['total']);
        $this->assertCount(3, $responseData['_embedded']['tasks']);

        $embedded = $responseData['_embedded']['tasks'];
        for ($i = 0, $length = count($postData); $i < $length; ++$i) {
            $this->assertContains(
                [
                    'id' => $postData[$i]['id'],
                    'schedule' => $postData[$i]['schedule'],
                    'handlerClass' => $postData[$i]['handlerClass'],
                    'taskName' => $postData[$i]['taskName'],
                    'status' => $postData[$i]['status'],
                ],
                $embedded
            );
        }
    }

    public function testPost(
        $handlerClass = FirstHandler::class,
        $schedule = '+1 day',
        $entityClass = 'ThisClass',
        $entityId = 1,
        $locale = 'de'
    ) {
        $date = new \DateTime($schedule);

        $this->client->request(
            'POST',
            '/api/tasks',
            [
                'handlerClass' => $handlerClass,
                'schedule' => $date->format('Y-m-d\TH:i:s'),
                'entityClass' => $entityClass,
                'entityId' => $entityId,
                'locale' => $locale,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($handlerClass, $responseData['handlerClass']);
        $this->assertEquals($date->format('Y-m-d\TH:i:s'), $responseData['schedule']);
        $this->assertNotNull($responseData['taskId']);
        $this->assertEquals($locale, $responseData['locale']);

        $taskManager = $this->getContainer()->get('sulu_automation.tasks.manager');
        $task = $taskManager->findById($responseData['id']);
        $this->assertEquals($handlerClass, $task->getHandlerClass());
        $this->assertEqualsWithDelta($date, $task->getSchedule(), 1);
        $this->assertNotNull($task->getTaskId());

        return $responseData;
    }

    public function testPut(
        $handlerClass = FirstHandler::class,
        $schedule = '+2 day',
        $entityClass = 'ThisClass',
        $locale = 'de'
    ) {
        $postData = $this->testPost();

        $date = new \DateTime($schedule);

        $this->client->request(
            'PUT',
            '/api/tasks/' . $postData['id'],
            [
                'handlerClass' => $handlerClass,
                'entityId' => $postData['entityId'],
                'taskId' => $postData['taskId'],
                'entityClass' => $entityClass,
                'locale' => $locale,
                'schedule' => $date->format('Y-m-d\TH:i:s'),
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($postData['id'], $responseData['id']);
        $this->assertEquals($handlerClass, $responseData['handlerClass']);
        $this->assertEquals($date->format('Y-m-d\TH:i:s'), $responseData['schedule']);
        $this->assertNotNull($responseData['taskId']);
        $this->assertEquals(FirstHandler::TITLE, $responseData['taskName']);

        $taskManager = $this->getContainer()->get('sulu_automation.tasks.manager');
        $task = $taskManager->findById($postData['id']);
        $this->assertEquals($handlerClass, $task->getHandlerClass());
        $this->assertEqualsWithDelta($date, $task->getSchedule(), 1);
        $this->assertNotNull($task->getTaskId());
    }

    public function testGet()
    {
        $postData = $this->testPost();

        $this->client->request('GET', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($postData['id'], $responseData['id']);
        $this->assertEquals($postData['handlerClass'], $responseData['handlerClass']);
        $this->assertEquals($postData['schedule'], $responseData['schedule']);
        $this->assertEquals($postData['locale'], $responseData['locale']);
    }

    public function testGetCount()
    {
        $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1, 'de');
        $this->testPost(SecondHandler::class, '+1 day', 'ThisClass', 1, 'de');
        $this->testPost(FirstHandler::class, '-1 day', 'ThisClass', 1, 'de');
        $this->testPost(FirstHandler::class, '+1 day', 'OtherClass', 1, 'de');
        $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 2, 'de');
        $this->testPost(FirstHandler::class, '+1 day', 'ThisClass', 1, 'en');

        $this->client->request('GET', '/api/task/count', [
            'entityClass' => 'ThisClass',
            'entityId' => 1,
            'locale' => 'de',
        ]);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(3, $responseData['count']);
    }

    public function testGetWithoutCreator()
    {
        $task = new Task();
        $task->setEntityClass(Task::class);
        $task->setEntityId(1);
        $task->setLocale('de');
        $task->setHandlerClass(FirstHandler::class);
        $task->setSchedule(new \DateTime());
        $task->setScheme('http');
        $task->setHost('sulu.io');

        $taskManager = $this->getContainer()->get('sulu_automation.tasks.manager');
        $taskManager->create($task);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $this->client->request('GET', '/api/tasks/' . $task->getId());
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($task->getId(), $responseData['id']);
        $this->assertEquals('', $responseData['creator']);
        $this->assertEquals('', $responseData['changer']);
    }

    public function testDelete()
    {
        $postData = $this->testPost();

        $this->client->request('DELETE', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/tasks/' . $postData['id']);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCDelete()
    {
        $postData = [
            $this->testPost(),
            $this->testPost(),
            $this->testPost(),
        ];

        $this->client->request('DELETE', '/api/tasks?ids=' . $postData[0]['id'] . ',' . $postData[1]['id']);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/tasks/' . $postData[0]['id']);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $this->client->request('GET', '/api/tasks/' . $postData[1]['id']);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
        $this->client->request('GET', '/api/tasks/' . $postData[2]['id']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($postData[2]['id'], $responseData['id']);
    }
}
