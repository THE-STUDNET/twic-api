<?php
namespace ModuleTest\Api\Service;

use ModuleTest\Api\AbstractService;

class EQCQTest extends AbstractService
{

    public static $session;

    public static function setUpBeforeClass()
    {
        system('phing -q reset-db deploy-db');
        
        parent::setUpBeforeClass();
    }

    public function testAddDimension()
    {
        $this->setIdentity(1);
        
        $data = $this->jsonRpc('dimension.add', array('name' => 'Dimension','describe' => 'une super dimension '));
        
        $this->assertEquals(count($data), 3);
        $this->assertEquals($data['result'], 3);
        $this->assertEquals($data['id'], 1);
        $this->assertEquals($data['jsonrpc'], 2.0);
        
        return $data['result'];
    }

    /**
     * @depends testAddDimension
     */
    public function testUpdateDimension($dimension)
    {
        $this->setIdentity(1);
        
        $data = $this->jsonRpc('dimension.update', array('id' => $dimension,'name' => 'Dimension UPT','describe' => 'une super dimension UPT'));
        
        $this->assertEquals(count($data), 3);
        $this->assertEquals($data['result'], 1);
        $this->assertEquals($data['id'], 1);
        $this->assertEquals($data['jsonrpc'], 2.0);
    }

    /**
     * @depends testAddDimension
     */
    public function testGetListDimension($dimension)
    {
        $this->setIdentity(1);
        
        $data = $this->jsonRpc('dimension.getList', array());
        
        $this->assertEquals(count($data), 3);
        $this->assertEquals(count($data['result']), 2);
        $this->assertEquals($data['result']['count'], 3);
        $this->assertEquals(count($data['result']['list']), 3);
        $this->assertEquals(count($data['result']['list'][0]), 5);
        $this->assertEquals(count($data['result']['list'][0]['component']), 4);
        $this->assertEquals(count($data['result']['list'][0]['component'][0]), 6);
        $this->assertEquals(count($data['result']['list'][0]['component'][0]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][0]['component'][0]['id'], 1);
        $this->assertEquals($data['result']['list'][0]['component'][0]['name'], "Awareness");
        $this->assertEquals($data['result']['list'][0]['component'][0]['dimension_id'], 1);
        $this->assertEquals(! empty($data['result']['list'][0]['component'][0]['describe']), true);
        $this->assertEquals(count($data['result']['list'][0]['component'][1]), 6);
        $this->assertEquals(count($data['result']['list'][0]['component'][1]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][0]['component'][1]['id'], 2);
        $this->assertEquals($data['result']['list'][0]['component'][1]['name'], "Literacy");
        $this->assertEquals($data['result']['list'][0]['component'][1]['dimension_id'], 1);
        $this->assertEquals(! empty($data['result']['list'][0]['component'][1]['describe']), true);
        $this->assertEquals(count($data['result']['list'][0]['component'][2]), 6);
        $this->assertEquals(count($data['result']['list'][0]['component'][2]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][0]['component'][2]['id'], 3);
        $this->assertEquals($data['result']['list'][0]['component'][2]['name'], "Impulse");
        $this->assertEquals($data['result']['list'][0]['component'][2]['dimension_id'], 1);
        $this->assertEquals(! empty($data['result']['list'][0]['component'][2]['describe']), true);
        $this->assertEquals(count($data['result']['list'][0]['component'][3]), 6);
        $this->assertEquals(count($data['result']['list'][0]['component'][3]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][0]['component'][3]['id'], 4);
        $this->assertEquals($data['result']['list'][0]['component'][3]['name'], "Performance");
        $this->assertEquals($data['result']['list'][0]['component'][3]['dimension_id'], 1);
        $this->assertEquals(! empty($data['result']['list'][0]['component'][3]['describe']), true);
        $this->assertEquals($data['result']['list'][0]['id'], 1);
        $this->assertEquals($data['result']['list'][0]['name'], "CQ");
        $this->assertEquals(! empty($data['result']['list'][0]['describe']), true);
        $this->assertEquals($data['result']['list'][0]['deleted_date'], null);
        $this->assertEquals(count($data['result']['list'][1]), 5);
        $this->assertEquals(count($data['result']['list'][1]['component']), 7);
        $this->assertEquals(count($data['result']['list'][1]['component'][0]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][0]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][0]['id'], 5);
        $this->assertEquals($data['result']['list'][1]['component'][0]['name'], "Positive drive");
        $this->assertEquals($data['result']['list'][1]['component'][0]['dimension_id'], 2);
        $this->assertEquals($data['result']['list'][1]['component'][0]['describe'], "Describe Positive drive");
        $this->assertEquals(count($data['result']['list'][1]['component'][1]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][1]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][1]['id'], 6);
        $this->assertEquals($data['result']['list'][1]['component'][1]['name'], "Empathy");
        $this->assertEquals($data['result']['list'][1]['component'][1]['dimension_id'], 2);
        $this->assertEquals($data['result']['list'][1]['component'][1]['describe'], "Describe Empathy");
        $this->assertEquals(count($data['result']['list'][1]['component'][2]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][2]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][2]['id'], 7);
        $this->assertEquals($data['result']['list'][1]['component'][2]['name'], "Happy Emotions");
        $this->assertEquals($data['result']['list'][1]['component'][2]['dimension_id'], 2);
        $this->assertEquals(! empty($data['result']['list'][1]['component'][2]['describe']), true);
        $this->assertEquals(count($data['result']['list'][1]['component'][3]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][3]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][3]['id'], 8);
        $this->assertEquals($data['result']['list'][1]['component'][3]['name'], "Emotional Self-Awareness");
        $this->assertEquals($data['result']['list'][1]['component'][3]['dimension_id'], 2);
        $this->assertEquals(! empty($data['result']['list'][1]['component'][3]['describe']), true);
        $this->assertEquals(count($data['result']['list'][1]['component'][4]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][4]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][4]['id'], 9);
        $this->assertEquals($data['result']['list'][1]['component'][4]['name'], "Emotional Display");
        $this->assertEquals($data['result']['list'][1]['component'][4]['dimension_id'], 2);
        $this->assertEquals(! empty($data['result']['list'][1]['component'][4]['describe']), true);
        $this->assertEquals(count($data['result']['list'][1]['component'][5]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][5]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][5]['id'], 10);
        $this->assertEquals($data['result']['list'][1]['component'][5]['name'], "Emotional Management");
        $this->assertEquals($data['result']['list'][1]['component'][5]['dimension_id'], 2);
        $this->assertEquals(! empty($data['result']['list'][1]['component'][5]['describe']), true);
        $this->assertEquals(count($data['result']['list'][1]['component'][6]), 6);
        $this->assertEquals(count($data['result']['list'][1]['component'][6]['component_scales']), 0);
        $this->assertEquals($data['result']['list'][1]['component'][6]['id'], 11);
        $this->assertEquals($data['result']['list'][1]['component'][6]['name'], "Non-specific");
        $this->assertEquals($data['result']['list'][1]['component'][6]['dimension_id'], 2);
        $this->assertEquals($data['result']['list'][1]['component'][6]['describe'], "Non-specific");
        $this->assertEquals($data['result']['list'][1]['id'], 2);
        $this->assertEquals($data['result']['list'][1]['name'], "EQ");
        $this->assertEquals(! empty($data['result']['list'][1]['describe']), true);
        $this->assertEquals($data['result']['list'][1]['deleted_date'], null);
        $this->assertEquals(count($data['result']['list'][2]), 5);
        $this->assertEquals(count($data['result']['list'][2]['component']), 0);
        $this->assertEquals($data['result']['list'][2]['id'], 3);
        $this->assertEquals($data['result']['list'][2]['name'], null);
        $this->assertEquals($data['result']['list'][2]['describe'], "une super dimension UPT");
        $this->assertEquals($data['result']['list'][2]['deleted_date'], null);
        $this->assertEquals($data['id'], 1);
        $this->assertEquals($data['jsonrpc'], 2.0);
    }

    public function testAddQuestion()
    {
        $this->setIdentity(1);
        
        $data = $this->jsonRpc(
            'question.add', array(
                'text' => 'super texte de fou',
                'component' => 1
        ));
        
        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['result'] , 52);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals($data['jsonrpc'] , 2.0);
        
        return $data['result'];
        
    }
    
    /**
     * @depends testAddQuestion
     */
    public function testUpdateQuestion($question)
    {
        $this->setIdentity(1);
    
        $data = $this->jsonRpc(
            'question.update', array(
                'id' => $question,
                'text' => 'super texte de fou deux',
                'component' => 2
            ));
    
        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['result'] , 1);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }
    
    /**
     * @depends testAddQuestion
     */
    public function testDeleteQuestion($question)
    {
        $this->setIdentity(1);
    
        $data = $this->jsonRpc(
            'question.delete', array(
                'id' => $question,
            ));
    
        $this->assertEquals(count($data) , 3);
        $this->assertEquals($data['result'] , 1);
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }
    
    /**
     * @depends testAddQuestion
     */
    public function testGetListQuestion($question)
    {
        $this->setIdentity(1);
    
        $data = $this->jsonRpc(
            'question.getList', array()
            );
    
        $this->assertEquals(count($data) , 3);
        $this->assertEquals(count($data['result']) , 52);
        $this->assertEquals(count($data['result'][0]) , 2);
        $this->assertEquals($data['result'][0]['id'] , 1);
        $this->assertEquals($data['result'][0]['text'] , "I am aware of the type of specific cultural knowledge which is required to interact with people from different cultural contexts.");
        $this->assertEquals(count($data['result'][1]) , 2);
        $this->assertEquals($data['result'][1]['id'] , 2);
        $this->assertEquals($data['result'][1]['text'] , "I automatically adapt my cultural stance and knowledge when I interact with people coming from a different culture than mine.");
        $this->assertEquals(count($data['result'][2]) , 2);
        $this->assertEquals($data['result'][2]['id'] , 3);
        $this->assertEquals($data['result'][2]['text'] , "I can identify the type of cultural knowledge which is required in various cross-cultural contexts.");
        $this->assertEquals(count($data['result'][3]) , 2);
        $this->assertEquals($data['result'][3]['id'] , 4);
        $this->assertEquals($data['result'][3]['text'] , "I test the validity and the accuracy of my cultural knowledge while dealing with people from different cultures.");
        $this->assertEquals(count($data['result'][4]) , 2);
        $this->assertEquals($data['result'][4]['id'] , 5);
        $this->assertEquals($data['result'][4]['text'] , "I know the legal and the economic environment of other cultures.");
        $this->assertEquals(count($data['result'][5]) , 2);
        $this->assertEquals($data['result'][5]['id'] , 6);
        $this->assertEquals($data['result'][5]['text'] , "I know foreign languages.");
        $this->assertEquals(count($data['result'][6]) , 2);
        $this->assertEquals($data['result'][6]['id'] , 7);
        $this->assertEquals($data['result'][6]['text'] , "I know other cultures religions and values.");
        $this->assertEquals(count($data['result'][7]) , 2);
        $this->assertEquals($data['result'][7]['id'] , 8);
        $this->assertEquals($data['result'][7]['text'] , "I know the artistic heritage and craft of other cultures.");
        $this->assertEquals(count($data['result'][8]) , 2);
        $this->assertEquals($data['result'][8]['id'] , 9);
        $this->assertEquals($data['result'][8]['text'] , "I know the rules of non-verbal communication in other cultures.");
        $this->assertEquals(count($data['result'][9]) , 2);
        $this->assertEquals($data['result'][9]['id'] , 10);
        $this->assertEquals($data['result'][9]['text'] , "I like dealing with people from different cultures.");
        $this->assertEquals(count($data['result'][10]) , 2);
        $this->assertEquals($data['result'][10]['id'] , 11);
        $this->assertEquals($data['result'][10]['text'] , "I am secure when I have to socialize with people coming from unfamiliar cultures.");
        $this->assertEquals(count($data['result'][11]) , 2);
        $this->assertEquals($data['result'][11]['id'] , 12);
        $this->assertEquals($data['result'][11]['text'] , "I can handle the stress of adapting to new cultures.");
        $this->assertEquals(count($data['result'][12]) , 2);
        $this->assertEquals($data['result'][12]['id'] , 13);
        $this->assertEquals($data['result'][12]['text'] , "I like living in different cultural contexts.");
        $this->assertEquals(count($data['result'][13]) , 2);
        $this->assertEquals($data['result'][13]['id'] , 14);
        $this->assertEquals($data['result'][13]['text'] , "I am sure that I can get used to living, shopping, and eating conditions in different cultures.");
        $this->assertEquals(count($data['result'][14]) , 2);
        $this->assertEquals($data['result'][14]['id'] , 15);
        $this->assertEquals($data['result'][14]['text'] , "I adapt my verbal communication when cross-cultural interactions require it.");
        $this->assertEquals(count($data['result'][15]) , 2);
        $this->assertEquals($data['result'][15]['id'] , 16);
        $this->assertEquals($data['result'][15]['text'] , "The pace of my talk is different depending on the cultural context.");
        $this->assertEquals(count($data['result'][16]) , 2);
        $this->assertEquals($data['result'][16]['id'] , 17);
        $this->assertEquals($data['result'][16]['text'] , "I adjust my non-verbal communication when cross-cultural interactions require it.");
        $this->assertEquals(count($data['result'][17]) , 2);
        $this->assertEquals($data['result'][17]['id'] , 18);
        $this->assertEquals($data['result'][17]['text'] , "I adapt the expression of my face according to the cultural context.");
        $this->assertEquals(count($data['result'][18]) , 2);
        $this->assertEquals($data['result'][18]['id'] , 19);
        $this->assertEquals($data['result'][18]['text'] , "When I am faced with obstacles, I remember times I faced similar obstacles and overcame them.");
        $this->assertEquals(count($data['result'][19]) , 2);
        $this->assertEquals($data['result'][19]['id'] , 20);
        $this->assertEquals($data['result'][19]['text'] , "I expect that I will do well on most things I try.");
        $this->assertEquals(count($data['result'][20]) , 2);
        $this->assertEquals($data['result'][20]['id'] , 21);
        $this->assertEquals($data['result'][20]['text'] , "Some of the major events of my life have led me to re?evaluate what is important and not important.");
        $this->assertEquals(count($data['result'][21]) , 2);
        $this->assertEquals($data['result'][21]['id'] , 22);
        $this->assertEquals($data['result'][21]['text'] , "I expect good things to happen.");
        $this->assertEquals(count($data['result'][22]) , 2);
        $this->assertEquals($data['result'][22]['id'] , 23);
        $this->assertEquals($data['result'][22]['text'] , "When I am in a positive mood, solving  problems is easy for me.");
        $this->assertEquals(count($data['result'][23]) , 2);
        $this->assertEquals($data['result'][23]['id'] , 24);
        $this->assertEquals($data['result'][23]['text'] , "When I am in a positive mood, I am able to come up with new ideas.");
        $this->assertEquals(count($data['result'][24]) , 2);
        $this->assertEquals($data['result'][24]['id'] , 25);
        $this->assertEquals($data['result'][24]['text'] , "I motivate myself by imagining a good outcome to tasks I take on.");
        $this->assertEquals(count($data['result'][25]) , 2);
        $this->assertEquals($data['result'][25]['id'] , 26);
        $this->assertEquals($data['result'][25]['text'] , "Other people find it easy to confide in me.");
        $this->assertEquals(count($data['result'][26]) , 2);
        $this->assertEquals($data['result'][26]['id'] , 27);
        $this->assertEquals($data['result'][26]['text'] , "By looking at their facial expressions, I recognize the     emotions people are experiencing.");
        $this->assertEquals(count($data['result'][27]) , 2);
        $this->assertEquals($data['result'][27]['id'] , 28);
        $this->assertEquals($data['result'][27]['text'] , "When another person tells me about an important event in  his or her life, I almost feel as though I experienced this event myself.");
        $this->assertEquals(count($data['result'][28]) , 2);
        $this->assertEquals($data['result'][28]['id'] , 29);
        $this->assertEquals($data['result'][28]['text'] , "When I feel a change in emotions, I tend to come up   with new ideas.");
        $this->assertEquals(count($data['result'][29]) , 2);
        $this->assertEquals($data['result'][29]['id'] , 30);
        $this->assertEquals($data['result'][29]['text'] , "I know what other people are feeling just by looking at them.");
        $this->assertEquals(count($data['result'][30]) , 2);
        $this->assertEquals($data['result'][30]['id'] , 31);
        $this->assertEquals($data['result'][30]['text'] , "I help other people feel better when  they are down.");
        $this->assertEquals(count($data['result'][31]) , 2);
        $this->assertEquals($data['result'][31]['id'] , 32);
        $this->assertEquals($data['result'][31]['text'] , "I can tell how people are feeling by listening to the tone of their voice.");
        $this->assertEquals(count($data['result'][32]) , 2);
        $this->assertEquals($data['result'][32]['id'] , 33);
        $this->assertEquals($data['result'][32]['text'] , "It is difficult for me to understand why people feel the way  they do.");
        $this->assertEquals(count($data['result'][33]) , 2);
        $this->assertEquals($data['result'][33]['id'] , 34);
        $this->assertEquals($data['result'][33]['text'] , "When I experience a positive emotion, I know how to  make it last.");
        $this->assertEquals(count($data['result'][34]) , 2);
        $this->assertEquals($data['result'][34]['id'] , 35);
        $this->assertEquals($data['result'][34]['text'] , "I arrange events others enjoy.");
        $this->assertEquals(count($data['result'][35]) , 2);
        $this->assertEquals($data['result'][35]['id'] , 36);
        $this->assertEquals($data['result'][35]['text'] , "I seek out activities that make me happy.");
        $this->assertEquals(count($data['result'][36]) , 2);
        $this->assertEquals($data['result'][36]['id'] , 37);
        $this->assertEquals($data['result'][36]['text'] , "I use good moods to help myself keep trying in the face of obstacles.");
        $this->assertEquals(count($data['result'][37]) , 2);
        $this->assertEquals($data['result'][37]['id'] , 38);
        $this->assertEquals($data['result'][37]['text'] , "Emotions are one of the things that make my life worth living.");
        $this->assertEquals(count($data['result'][38]) , 2);
        $this->assertEquals($data['result'][38]['id'] , 39);
        $this->assertEquals($data['result'][38]['text'] , "I am aware of my emotions as I experience them.");
        $this->assertEquals(count($data['result'][39]) , 2);
        $this->assertEquals($data['result'][39]['id'] , 40);
        $this->assertEquals($data['result'][39]['text'] , "I know why my emotions change.");
        $this->assertEquals(count($data['result'][40]) , 2);
        $this->assertEquals($data['result'][40]['id'] , 41);
        $this->assertEquals($data['result'][40]['text'] , "I easily recognize my emotions as I experience them.");
        $this->assertEquals(count($data['result'][41]) , 2);
        $this->assertEquals($data['result'][41]['id'] , 42);
        $this->assertEquals($data['result'][41]['text'] , "I find it hard to understand the non?verbal messages of other.");
        $this->assertEquals(count($data['result'][42]) , 2);
        $this->assertEquals($data['result'][42]['id'] , 43);
        $this->assertEquals($data['result'][42]['text'] , "I am aware of the non?verbal messages I send to others.");
        $this->assertEquals(count($data['result'][43]) , 2);
        $this->assertEquals($data['result'][43]['id'] , 44);
        $this->assertEquals($data['result'][43]['text'] , "I am aware of the non?verbal messages other people send.");
        $this->assertEquals(count($data['result'][44]) , 2);
        $this->assertEquals($data['result'][44]['id'] , 45);
        $this->assertEquals($data['result'][44]['text'] , "I know when to speak about my personal problems to others.");
        $this->assertEquals(count($data['result'][45]) , 2);
        $this->assertEquals($data['result'][45]['id'] , 46);
        $this->assertEquals($data['result'][45]['text'] , "I have control over my emotions.");
        $this->assertEquals(count($data['result'][46]) , 2);
        $this->assertEquals($data['result'][46]['id'] , 47);
        $this->assertEquals($data['result'][46]['text'] , "I compliment others when they have done something well.");
        $this->assertEquals(count($data['result'][47]) , 2);
        $this->assertEquals($data['result'][47]['id'] , 48);
        $this->assertEquals($data['result'][47]['text'] , "When I am faced with a challenge, I give up because.");
        $this->assertEquals(count($data['result'][48]) , 2);
        $this->assertEquals($data['result'][48]['id'] , 49);
        $this->assertEquals($data['result'][48]['text'] , "When my mood changes, I see new possibilities.");
        $this->assertEquals(count($data['result'][49]) , 2);
        $this->assertEquals($data['result'][49]['id'] , 50);
        $this->assertEquals(!empty($data['result'][49]['text']) , true);
        $this->assertEquals(count($data['result'][50]) , 2);
        $this->assertEquals($data['result'][50]['id'] , 51);
        $this->assertEquals($data['result'][50]['text'] , "I present myself in a way that makes a good impression on others.");
        $this->assertEquals(count($data['result'][51]) , 2);
        $this->assertEquals($data['result'][51]['id'] , 52);
        $this->assertEquals($data['result'][51]['text'] , "super texte de fou deux");
        $this->assertEquals($data['id'] , 1);
        $this->assertEquals($data['jsonrpc'] , 2.0);
    }
    

    public function setIdentity($id)
    {
        $identityMock = $this->getMockBuilder('\Auth\Authentication\Adapter\Model\Identity')
            ->disableOriginalConstructor()
            ->getMock();
        
        $rbacMock = $this->getMockBuilder('\Rbac\Service\Rbac')
            ->disableOriginalConstructor()
            ->getMock();
        
        $identityMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        
        $identityMock->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(array('id' => $id)));
        
        $authMock = $this->getMockBuilder('\Zend\Authentication\AuthenticationService')
            ->disableOriginalConstructor()
            ->getMock();
        
        $authMock->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue($identityMock));
        
        $authMock->expects($this->any())
            ->method('hasIdentity')
            ->will($this->returnValue(true));
        
        $rbacMock->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));
        
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('auth.service', $authMock);
        $serviceManager->setService('rbac.service', $rbacMock);
    }
}