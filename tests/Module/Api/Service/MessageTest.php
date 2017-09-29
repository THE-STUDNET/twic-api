<?php
namespace ModuleTest\Api\Service;

use ModuleTest\Api\AbstractService;

class MessageTest extends AbstractService
{
    public static function setUpBeforeClass()
    {
        system('phing -q reset-db deploy-db');

        parent::setUpBeforeClass();
    }

    public function testInit()
    {
        $this->setIdentity(1, 1);
        $data = $this->jsonRpc('page.add', [
          'title' => 'super title',
          'logo' => 'logo',
          'background' => 'background',
          'description' => 'description',
          'confidentiality' => 1,
          'type' => 'organization',
          'admission' => 'free',
          'start_date' => '2015-00-00 00:00:00',
          'end_date' => '2016-00-00 00:00:00',
          'location' => 'location',
          'organization_id' => 1,
          'users' => [
              ['user_id' => 1,'role' => 'admin', 'state' => 'member'],
              ['user_id' => 2,'role' => 'admin', 'state' => 'member'],
              ['user_id' => 3,'role' => 'admin', 'state' => 'member'],
          ],
        ]);

        $page_id = $data['id'];
        $this->reset();
        $this->setIdentity(1);
        $data = $this->jsonRpc('user.addOrganization', [
          'organization_id' => $page_id,
          'user_id' => [1,2,3,4,5,6,7],
          'default' => true
        ]);

        $this->reset();
        $this->setIdentity(1);
        $data = $this->jsonRpc('circle.add', [
            'name' => 'gnam'
        ]);

        $circle_id = $data['result'];

        $this->reset();
        $this->setIdentity(1);
        $data = $this->jsonRpc('circle.addOrganizations', [
            'id' => $circle_id,
            'organizations' => [$page_id]
        ]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 1);
        $this->assertEquals($data['result'][1] , 1);
        $this->assertEquals($data['jsonrpc'] , 2.0);

        return $page_id;
    }

    public function testCanSendMessage()
    {
        $this->setIdentity(1);
        $data = $this->jsonRpc('message.send', ['to' =>[2,3],'text' => 'super message qwerty']);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals($data['result']['message_id'] , 1);
        $this->assertEquals($data['result']['conversation_id'] , 1);
        $this->assertEquals($data['jsonrpc'] , 2.0);

        return $data['result'];
    }

    public function testCanSendMessageTwo()
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('message.send', ['to' => 3,'text' => 'super message deux qwerty 1']);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals($data['result']['message_id'] , 2);
        $this->assertEquals($data['result']['conversation_id'] , 2);
        $this->assertEquals($data['jsonrpc'] , 2.0);

        return $data['result'];
    }

    public function testCanSendMessagethree()
    {
        $this->setIdentity(3);
        $data = $this->jsonRpc('message.send', ['to' => 2,'text' => 'super message un azerty 2']);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals($data['result']['message_id'] , 3);
        $this->assertEquals($data['result']['conversation_id'] , 2);
        $this->assertEquals($data['jsonrpc'] , 2.0);

        return $data['result'];
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanSendMessagethreebis($conv)
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('message.send', [
          'conversation_id' => $conv['conversation_id'], 'text' => 'dernier message', 'library' => ['token' => '123456789', 'name' => 'super doc  ']]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals($data['result']['message_id'] , 4);
        $this->assertEquals($data['result']['conversation_id'] , 2);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanGetIdConv($conv)
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('conversation.getIdByUser', [
          'user_id' => [3]
        ]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals($data['result'] , $conv['conversation_id']);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanGetIdConvAddVideo($conv)
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('conversation.addVideo', [
          'id' => $conv['conversation_id']
        ]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(!empty($data['result']) , true);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanGetIdConvGetToken($conv)
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('conversation.getToken', [
          'id' => $conv['conversation_id']
        ]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 3);
        $this->assertEquals(!empty($data['result']['token']) , true);
        $this->assertEquals(!empty($data['result']['session']) , true);
        $this->assertEquals(!empty($data['result']['role']) , true);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanGet($conv)
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('conversation.get', [
          'id' => $conv
        ]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 1);
        $this->assertEquals(count($data['result'][2]) , 9);
        $this->assertEquals($data['result'][2]['item_id'] , null);
        $this->assertEquals(count($data['result'][2]['message']) , 6);
        $this->assertEquals(count($data['result'][2]['message']['message_user']) , 1);
        $this->assertEquals(!empty($data['result'][2]['message']['message_user']['read_date']) , true);
        $this->assertEquals(count($data['result'][2]['message']['library']) , 6);
        $this->assertEquals($data['result'][2]['message']['library']['id'] , 4);
        $this->assertEquals($data['result'][2]['message']['library']['name'] , "super doc  ");
        $this->assertEquals($data['result'][2]['message']['library']['link'] , null);
        $this->assertEquals($data['result'][2]['message']['library']['token'] , 123456789);
        $this->assertEquals($data['result'][2]['message']['library']['type'] , null);
        $this->assertEquals($data['result'][2]['message']['library']['box_id'] , null);
        $this->assertEquals($data['result'][2]['message']['id'] , 4);
        $this->assertEquals($data['result'][2]['message']['text'] , "dernier message");
        $this->assertEquals(!empty($data['result'][2]['message']['created_date']) , true);
        $this->assertEquals($data['result'][2]['message']['user_id'] , 2);
        $this->assertEquals(count($data['result'][2]['users']) , 2);
        $this->assertEquals($data['result'][2]['users'][0] , 2);
        $this->assertEquals($data['result'][2]['users'][1] , 3);
        $this->assertEquals($data['result'][2]['nb_users'] , 2);
        $this->assertEquals($data['result'][2]['page_id'] , null);
        $this->assertEquals($data['result'][2]['id'] , 2);
        $this->assertEquals($data['result'][2]['name'] , "Chat");
        $this->assertEquals($data['result'][2]['type'] , 2);
        $this->assertEquals(!empty($data['result'][2]['options']) , true);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanGetList($conv)
    {
        $this->setIdentity(2);
        $data = $this->jsonRpc('conversation.getList', [
        ]);
        
        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals(count($data['result'][0]) , 8);
        $this->assertEquals($data['result'][0]['item_id'] , null);
        $this->assertEquals(count($data['result'][0]['message']) , 1);
        $this->assertEquals($data['result'][0]['message']['id'] , 4);
        $this->assertEquals(count($data['result'][0]['users']) , 2);
        $this->assertEquals($data['result'][0]['users'][0] , 2);
        $this->assertEquals($data['result'][0]['users'][1] , 3);
        $this->assertEquals($data['result'][0]['nb_users'] , 2);
        $this->assertEquals($data['result'][0]['page_id'] , null);
        $this->assertEquals($data['result'][0]['id'] , 2);
        $this->assertEquals($data['result'][0]['name'] , "Chat");
        $this->assertEquals($data['result'][0]['type'] , 2);
        $this->assertEquals(count($data['result'][1]) , 8);
        $this->assertEquals($data['result'][1]['item_id'] , null);
        $this->assertEquals(count($data['result'][1]['message']) , 1);
        $this->assertEquals($data['result'][1]['message']['id'] , 1);
        $this->assertEquals(count($data['result'][1]['users']) , 3);
        $this->assertEquals($data['result'][1]['users'][0] , 1);
        $this->assertEquals($data['result'][1]['users'][1] , 2);
        $this->assertEquals($data['result'][1]['users'][2] , 3);
        $this->assertEquals($data['result'][1]['nb_users'] , 3);
        $this->assertEquals($data['result'][1]['page_id'] , null);
        $this->assertEquals($data['result'][1]['id'] , 1);
        $this->assertEquals($data['result'][1]['name'] , "Chat");
        $this->assertEquals($data['result'][1]['type'] , 2);
        $this->assertEquals($data['jsonrpc'] , 2.0); 
    }

    public function testCanSendMessageunadeux()
    {
        $this->setIdentity(1);
        $data = $this->jsonRpc('message.send', ['to' => 2,'text' => 'super message un azerty 2']);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals($data['result']['message_id'] , 5);
        $this->assertEquals($data['result']['conversation_id'] , 3);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageunadeux
     */
    public function testCanGetListConv()
    {

      $this->setIdentity(2);
      $data = $this->jsonRpc('contact.add', [
        'user' => 3
      ]);
      $this->reset();
      $this->setIdentity(3);
      $data = $this->jsonRpc('contact.accept', [
        'user' => 2
      ]);
      $this->reset();


      // 2 => 3
      $this->setIdentity(2);
      $data = $this->jsonRpc('conversation.getList', [
        'contact' => true
      ]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 1);
      $this->assertEquals(count($data['result'][0]) , 8);
      $this->assertEquals($data['result'][0]['item_id'] , null);
      $this->assertEquals(count($data['result'][0]['message']) , 1);
      $this->assertEquals($data['result'][0]['message']['id'] , 4);
      $this->assertEquals(count($data['result'][0]['users']) , 2);
      $this->assertEquals($data['result'][0]['users'][0] , 2);
      $this->assertEquals($data['result'][0]['users'][1] , 3);
      $this->assertEquals($data['result'][0]['nb_users'] , 2);
      $this->assertEquals($data['result'][0]['page_id'] , null);
      $this->assertEquals($data['result'][0]['id'] , 2);
      $this->assertEquals($data['result'][0]['name'] , "Chat");
      $this->assertEquals($data['result'][0]['type'] , 2);
      $this->assertEquals($data['jsonrpc'] , 2.0); 
    }

    /**
     * @depends testCanSendMessageunadeux
     */
    public function testCanGetListContact()
    {

      $this->setIdentity(2);
      $data = $this->jsonRpc('conversation.getList', [
        'contact' => false,
      ]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 2);
      $this->assertEquals(count($data['result'][0]) , 8);
      $this->assertEquals($data['result'][0]['item_id'] , null);
      $this->assertEquals(count($data['result'][0]['message']) , 1);
      $this->assertEquals($data['result'][0]['message']['id'] , 5);
      $this->assertEquals(count($data['result'][0]['users']) , 2);
      $this->assertEquals($data['result'][0]['users'][0] , 1);
      $this->assertEquals($data['result'][0]['users'][1] , 2);
      $this->assertEquals($data['result'][0]['nb_users'] , 2);
      $this->assertEquals($data['result'][0]['page_id'] , null);
      $this->assertEquals($data['result'][0]['id'] , 3);
      $this->assertEquals($data['result'][0]['name'] , "Chat");
      $this->assertEquals($data['result'][0]['type'] , 2);
      $this->assertEquals(count($data['result'][1]) , 8);
      $this->assertEquals($data['result'][1]['item_id'] , null);
      $this->assertEquals(count($data['result'][1]['message']) , 1);
      $this->assertEquals($data['result'][1]['message']['id'] , 1);
      $this->assertEquals(count($data['result'][1]['users']) , 3);
      $this->assertEquals($data['result'][1]['users'][0] , 1);
      $this->assertEquals($data['result'][1]['users'][1] , 2);
      $this->assertEquals($data['result'][1]['users'][2] , 3);
      $this->assertEquals($data['result'][1]['nb_users'] , 3);
      $this->assertEquals($data['result'][1]['page_id'] , null);
      $this->assertEquals($data['result'][1]['id'] , 1);
      $this->assertEquals($data['result'][1]['name'] , "Chat");
      $this->assertEquals($data['result'][1]['type'] , 2);
      $this->assertEquals($data['jsonrpc'] , 2.0); 
    }

    /**
     * Terst du read
     *
     * @depends testCanSendMessageunadeux
     */
    public function testCanGetListConvBeforeRead()
    {
      // 2 => 3
      $this->setIdentity(3);
      $data = $this->jsonRpc('conversation.getList', [
        'noread' => true
      ]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 2);
      $this->assertEquals(count($data['result'][0]) , 8);
      $this->assertEquals($data['result'][0]['item_id'] , null);
      $this->assertEquals(count($data['result'][0]['message']) , 1);
      $this->assertEquals($data['result'][0]['message']['id'] , 4);
      $this->assertEquals(count($data['result'][0]['users']) , 2);
      $this->assertEquals($data['result'][0]['users'][0] , 2);
      $this->assertEquals($data['result'][0]['users'][1] , 3);
      $this->assertEquals($data['result'][0]['nb_users'] , 2);
      $this->assertEquals($data['result'][0]['page_id'] , null);
      $this->assertEquals($data['result'][0]['id'] , 2);
      $this->assertEquals($data['result'][0]['name'] , "Chat");
      $this->assertEquals($data['result'][0]['type'] , 2);
      $this->assertEquals(count($data['result'][1]) , 8);
      $this->assertEquals($data['result'][1]['item_id'] , null);
      $this->assertEquals(count($data['result'][1]['message']) , 1);
      $this->assertEquals($data['result'][1]['message']['id'] , 1);
      $this->assertEquals(count($data['result'][1]['users']) , 3);
      $this->assertEquals($data['result'][1]['users'][0] , 1);
      $this->assertEquals($data['result'][1]['users'][1] , 2);
      $this->assertEquals($data['result'][1]['users'][2] , 3);
      $this->assertEquals($data['result'][1]['nb_users'] , 3);
      $this->assertEquals($data['result'][1]['page_id'] , null);
      $this->assertEquals($data['result'][1]['id'] , 1);
      $this->assertEquals($data['result'][1]['name'] , "Chat");
      $this->assertEquals($data['result'][1]['type'] , 2);
      $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanGetread($conv)
    {
      $this->setIdentity(3);
      $data = $this->jsonRpc('conversation.read', ['id' => $conv['conversation_id']]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals($data['result'] , 1);
      $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * Terst du read
     *
     * @depends testCanSendMessageunadeux
     */
    public function testCanGetListConvAfterRead()
    {
      // 2 => 3
      $this->setIdentity(3);
      $data = $this->jsonRpc('conversation.getList', [
        'noread' => true
      ]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 1);
      $this->assertEquals(count($data['result'][0]) , 8);
      $this->assertEquals($data['result'][0]['item_id'] , null);
      $this->assertEquals(count($data['result'][0]['message']) , 1);
      $this->assertEquals($data['result'][0]['message']['id'] , 1);
      $this->assertEquals(count($data['result'][0]['users']) , 3);
      $this->assertEquals($data['result'][0]['users'][0] , 1);
      $this->assertEquals($data['result'][0]['users'][1] , 2);
      $this->assertEquals($data['result'][0]['users'][2] , 3);
      $this->assertEquals($data['result'][0]['nb_users'] , 3);
      $this->assertEquals($data['result'][0]['page_id'] , null);
      $this->assertEquals($data['result'][0]['id'] , 1);
      $this->assertEquals($data['result'][0]['name'] , "Chat");
      $this->assertEquals($data['result'][0]['type'] , 2);
      $this->assertEquals($data['jsonrpc'] , 2.0); 
    }

    /**
    * Test la récupération des membre paginer
    **/
    public function testCanGetListId()
    {
        $this->setIdentity(1);
        $data = $this->jsonRpc('user.getListId', [
          'conversation_id' => 1,
          'filter' => [
            'p' => 1,
            'n' => 3,
            'c' => ['user.id' => '>'],
            's' => '2'
          ],
        ]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals(count($data['result']['list']) , 1);
        $this->assertEquals($data['result']['list'][0] , 3);
        $this->assertEquals($data['result']['count'] , 3);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanMessageGetList($conv)
    {
        $this->setIdentity(2);

        $data = $this->jsonRpc('message.getList', ['conversation_id' => $conv['conversation_id']]);

        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals(count($data['result']) , 2);
        $this->assertEquals(count($data['result']['list']) , 3);
        $this->assertEquals(count($data['result']['list'][0]) , 6);
        $this->assertEquals(count($data['result']['list'][0]['message_user']) , 1);
        $this->assertEquals(!empty($data['result']['list'][0]['message_user']['read_date']) , true);
        $this->assertEquals(count($data['result']['list'][0]['library']) , 6);
        $this->assertEquals($data['result']['list'][0]['library']['id'] , 4);
        $this->assertEquals($data['result']['list'][0]['library']['name'] , "super doc  ");
        $this->assertEquals($data['result']['list'][0]['library']['link'] , null);
        $this->assertEquals($data['result']['list'][0]['library']['token'] , 123456789);
        $this->assertEquals($data['result']['list'][0]['library']['type'] , null);
        $this->assertEquals($data['result']['list'][0]['library']['box_id'] , null);
        $this->assertEquals($data['result']['list'][0]['id'] , 4);
        $this->assertEquals($data['result']['list'][0]['text'] , "dernier message");
        $this->assertEquals(!empty($data['result']['list'][0]['created_date']) , true);
        $this->assertEquals($data['result']['list'][0]['user_id'] , 2);
        $this->assertEquals(count($data['result']['list'][1]) , 6);
        $this->assertEquals(count($data['result']['list'][1]['message_user']) , 1);
        $this->assertEquals(!empty($data['result']['list'][1]['message_user']['read_date']) , false);
        $this->assertEquals($data['result']['list'][1]['library'] , null);
        $this->assertEquals($data['result']['list'][1]['id'] , 3);
        $this->assertEquals($data['result']['list'][1]['text'] , "super message un azerty 2");
        $this->assertEquals(!empty($data['result']['list'][1]['created_date']) , true);
        $this->assertEquals($data['result']['list'][1]['user_id'] , 3);
        $this->assertEquals(count($data['result']['list'][2]) , 6);
        $this->assertEquals(count($data['result']['list'][2]['message_user']) , 1);
        $this->assertEquals(!empty($data['result']['list'][2]['message_user']['read_date']) , true);
        $this->assertEquals($data['result']['list'][2]['library'] , null);
        $this->assertEquals($data['result']['list'][2]['id'] , 2);
        $this->assertEquals($data['result']['list'][2]['text'] , "super message deux qwerty 1");
        $this->assertEquals(!empty($data['result']['list'][2]['created_date']) , true);
        $this->assertEquals($data['result']['list'][2]['user_id'] , 2);
        $this->assertEquals($data['result']['count'] , 3);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageunadeux
     */
    public function testCanGetListSearch()
    {
      $this->setIdentity(2);
      $data = $this->jsonRpc('conversation.getList', [
        'search' => 'paul',
        'filter' => ['n' => 1],
        'contact' => false
      ]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 2);
      $this->assertEquals(count($data['result']['list']) , 1);
      $this->assertEquals(count($data['result']['list'][0]) , 8);
      $this->assertEquals($data['result']['list'][0]['item_id'] , null);
      $this->assertEquals(count($data['result']['list'][0]['message']) , 1);
      $this->assertEquals($data['result']['list'][0]['message']['id'] , 5);
      $this->assertEquals(count($data['result']['list'][0]['users']) , 2);
      $this->assertEquals($data['result']['list'][0]['users'][0] , 1);
      $this->assertEquals($data['result']['list'][0]['users'][1] , 2);
      $this->assertEquals($data['result']['list'][0]['nb_users'] , 2);
      $this->assertEquals($data['result']['list'][0]['page_id'] , null);
      $this->assertEquals($data['result']['list'][0]['id'] , 3);
      $this->assertEquals($data['result']['list'][0]['name'] , "Chat");
      $this->assertEquals($data['result']['list'][0]['type'] , 2);
      $this->assertEquals($data['result']['count'] , 2);
      $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanStartRecord($conv)
    {
      //MOCK OPENTOK
      $serviceManager = $this->getApplicationServiceLocator();
      $mock = $this->getMockBuilder('\ZOpenTok\Service\OpenTok')
          ->disableOriginalConstructor()
          ->setMethods(['startArchive'])->getMock();
      $mock->expects($this->any())
              ->method('startArchive')
              ->willReturn(json_encode(['status' => 'started', 'id' => 1234]));

      $serviceManager->setAllowOverride(true);
      $serviceManager->setService('opentok.service', $mock);

      $this->setIdentity(2);
      $data = $this->jsonRpc('videoarchive.startRecord', ['conversation_id' => $conv['conversation_id']]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 2);
      $this->assertEquals($data['result']['status'] , "started");
      $this->assertEquals($data['result']['id'] , 1234);
      $this->assertEquals($data['jsonrpc'] , 2.0);
    }

    /**
     * @depends testCanSendMessageTwo
     */
    public function testCanStopRecord($conv)
    {
      //MOCK OPENTOK
      $serviceManager = $this->getApplicationServiceLocator();
      $mock = $this->getMockBuilder('\ZOpenTok\Service\OpenTok')
          ->disableOriginalConstructor()
          ->setMethods(['stopArchive'])->getMock();
      $mock->expects($this->any())
              ->method('stopArchive')
              ->willReturn(['status' => 'stop', 'id' => 1234]);

      $serviceManager->setAllowOverride(true);
      $serviceManager->setService('opentok.service', $mock);

      $this->setIdentity(2);
      $data = $this->jsonRpc('videoarchive.stopRecord', ['conversation_id' => $conv['conversation_id']]);

      $this->assertEquals(count($data) , 3);
      $this->assertEquals($data['id'] , 1);
      $this->assertEquals(count($data['result']) , 2);
      $this->assertEquals($data['result']['status'] , "stop");
      $this->assertEquals($data['result']['id'] , 1234);
      $this->assertEquals($data['jsonrpc'] , 2.0);
    }
}
