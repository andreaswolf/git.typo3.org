<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


require_once 'vfsStream/vfsStream.php';
require_once 'tests/t3lib/vfs/t3lib_vfs_nodeTestHelper.php';

/**
 * Testcase for the abstract basic node class of
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class t3lib_vfs_NodeTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_vfs_Node
	 */
	private $fixture;

	private $fixtureConstructorData;

	public function setUp() {
		$this->fixtureConstructorData = array(
			array(
				'propA' => uniqid(),
				'propB' => uniqid(),
				'name' => uniqid(),
				'pid' => uniqid()
			)
		);
		$this->fixture = $this->getMockForAbstractClass('t3lib_vfs_Node', $this->fixtureConstructorData);
	}

	/**
	 * @test
	 */
	public function constructorSavesProperties() {
		$this->assertEquals($this->fixtureConstructorData[0]['propA'], $this->fixture->getValue('propA'));
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::isMountpoint
	 * @covers t3lib_vfs_Node::isRootNode
	 */
	public function nodeIsNeitherMountpointNorRootNode() {
		$this->assertFalse($this->fixture->isMountpoint());
		$this->assertFalse($this->fixture->isRootNode());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::getName
	 */
	public function nodeNameIsSetFromConstructorArguments() {
		$this->assertEquals($this->fixtureConstructorData[0]['name'], $this->fixture->getName());
	}

	/**
	 * @test
	 */
	public function parentMayBeSetAndRetrieved() {
		$mockedParent = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array()));

		$this->fixture->setParent($mockedParent);
		$this->assertSame($mockedParent, $this->fixture->getParent());
	}

	/**
	 * @test
	 */
	public function settingParentChangesPidAttribute() {
		$parentUid = uniqid();
		$mockedParent = $this->getMock('t3lib_vfs_Node', array('getUid'), array(), '', FALSE);
		$mockedParent->expects($this->once())->method('getUid')->will($this->returnValue($parentUid));

		$fixture = $this->getMock('t3lib_vfs_Node', array('setValue'), array(), '', FALSE);
		$fixture->expects($this->once())->method('setValue')->with('pid', $parentUid);

		$fixture->setParent($mockedParent);
	}

	/**
	 * @test
	 */
	public function setValueChangesPropertyValues() {
		$newValue = uniqid();

		$this->fixture->setValue('propA', $newValue);

		$this->assertEquals($newValue, $this->fixture->getValue('propA'));
	}

	/**
	 * @test
	 */
	public function setValueFailsIfPropertyDoesntExist() {
		$this->setExpectedException('InvalidArgumentException', 1300127094);

		$this->fixture->setValue(uniqid(), uniqid());
	}

	/**
	 * @test
	 */
	public function setValueRegistersPropertyAsChanged() {
		$this->fixture->setValue('propA', uniqid());

		$changedProperties = $this->fixture->getChangedProperties();

		$this->assertArrayHasKey('propA', $changedProperties);
	}

	/**
	 * @test
	 */
	public function setValueKeepsOldValueInChangedPropertiesArray() {
		$oldValue = $this->fixture->getValue('propA');
		$this->fixture->setValue('propA', uniqid());

		$changedProperties = $this->fixture->getChangedProperties();
		$this->assertEquals($oldValue, $changedProperties['propA']);
	}

	/**
	 * @test
	 */
	public function setValueDoesntOverwriteOldValueIfItHasBeenChangedBefore() {
		$oldValue = $this->fixture->getValue('propA');
		$this->fixture->setValue('propA', uniqid());
		$this->fixture->setValue('propA', uniqid());

		$changedProperties = $this->fixture->getChangedProperties();
		$this->assertEquals($oldValue, $changedProperties['propA']);
	}

	/**
	 * @test
	 */
	public function changedPropertiesMayBeReset() {
		$this->fixture->setValue('propA', uniqid());

		$this->assertNotEmpty($this->fixture->getChangedProperties());

		$this->fixture->resetChangedProperties();

		$this->assertEmpty($this->fixture->getChangedProperties());
	}

	/**
	 * @test
	 */
	public function getChangedPropertiesReturnsAllChangedProperties() {
		$this->fixture->setValue('propA', uniqid());
		$this->fixture->setValue('propB', uniqid());

		$changedProperties = $this->fixture->getChangedProperties();

		$this->assertArrayHasKey('propA', $changedProperties);
		$this->assertArrayHasKey('propB', $changedProperties);
	}

	/**
	 * @test
	 */
	public function getChangedPropertyNamesReturnsNames() {
		$this->fixture->setValue('propA', uniqid());
		$this->fixture->setValue('propB', uniqid());

		$changedPropertyNames = $this->fixture->getChangedPropertyNames();

		$this->assertContains('propA', $changedPropertyNames);
		$this->assertContains('propB', $changedPropertyNames);
	}

	/**
	 * @test
	 */
	public function getPropertiesReturnsAllProperties() {
		$this->assertEquals($this->fixtureConstructorData[0], $this->fixture->getProperties());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::hasProperty
	 */
	public function hasPropertyReturnsFalseIfPropertyIsNotRegistered() {
		$this->fixture->hasProperty(uniqid());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::hasProperty
	 */
	public function hasPropertyRecognizesRegisteredProperties() {
		foreach ($this->fixture->getProperties() as $key => $value) {
			$this->assertTrue($this->fixture->hasProperty($key));
		}
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::__construct
	 */
	public function availablePropertiesAreSetOnObjectCreation() {
		$mockedClassName = uniqid('Mock_t3lib_vfs_Node');
		$classCode = 'class ' . $mockedClassName . ' extends t3lib_vfs_Node {
			protected $availableProperties = array("propA", "propB");
		}';
		eval($classCode);

		$fixture = new $mockedClassName(array('propC' => ''));
		$this->assertTrue($fixture->hasProperty('propA'));
		$this->assertTrue($fixture->hasProperty('propB'));
	}

	/**
	 * @test
	 */
	public function setMountpointSetsMountpoint() {
		$mockedMount = $this->getMock('t3lib_vfs_Mount', array(), array(), '', FALSE);

		$this->fixture->setMountpoint($mockedMount);

		$this->assertEquals($mockedMount, $this->fixture->getMountpoint());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::getPathInMountpoint
	 */
	public function getPathInMountpointReturnsCorrectPath() {
			// please note: the name of the mountpoint is not included in the path used here
		$mockedNodesData = array(
			array(uniqid('mount-'), 'Mount'), // Mount
			array(uniqid('folder-'), 'Folder'), // Folder 1
			array(uniqid('folder-'), 'Folder'), // Folder 2
			array(uniqid('file-'), 'File'), // File
		);

		list($mockedNodes, $pathParts) = t3lib_vfs_NodeTestHelper::prepareNodeHierarchyForPathTests($mockedNodesData, $this);

		$path = $mockedNodes[count($mockedNodes)-1]->getPathInMountpoint(TRUE);
		$this->assertEquals(implode('/', array_slice($pathParts, 1)), $path);

		$path = $mockedNodes[count($mockedNodes)-1]->getPathInMountpoint(FALSE);
		$this->assertEquals(implode('/', array_slice($pathParts, 1, -1)) . '/', $path);
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::getPath
	 */
	public function getPathReturnsCorrectPath() {
			// please note: the name of the mountpoint is not included in the path used here
		$mockedNodesData = array(
			array('', 'RootNode'),
			array(uniqid('mount-'), 'Mount'), // Mount 1
			array(uniqid('folder-'), 'Folder'), // Folder 1
			array(uniqid('mount-'), 'Mount'), // Mount 2
			array(uniqid('folder-'), 'Folder'), // Folder 2
			array(uniqid('file-'), 'File'), // File
		);

		list($mockedNodes, $pathParts) = t3lib_vfs_NodeTestHelper::prepareNodeHierarchyForPathTests($mockedNodesData, $this);

		$path = $mockedNodes[count($mockedNodes)-1]->getPath(TRUE);
		$this->assertEquals(implode('/', $pathParts), $path);

		$path = $mockedNodes[count($mockedNodes)-1]->getPath(FALSE);
		$this->assertEquals(implode('/', array_slice($pathParts, 0, -1)) . '/', $path);
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::getUid
	 */
	public function getUidReturnsMinusOneForNewRecords() {
		/** @var $fixture t3lib_vfs_Node */
		$fixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array()));

		$this->assertEquals(-1, $fixture->getUid());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::getUid
	 * @covers t3lib_vfs_Node::__construct
	 *
	 * NOTE: This also tests if the uid is correctly extracted from the properties.
	 */
	public function getUidReturnsCorrectValueForExistingRecord() {
		$uid = uniqid();
		/** @var $fixture t3lib_vfs_Node */
		$fixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array('uid' => $uid)));

		$this->assertEquals($uid, $fixture->getUid());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::setUid
	 */
	public function setUidSetsUidForNewRecords() {
		$uid = uniqid();
		/** @var $fixture t3lib_vfs_Node */
		$fixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array()));

		$fixture->setUid($uid);
		$this->assertEquals($uid, $fixture->getUid());
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::setUid
	 */
	public function setUidThrowsExceptionForExistingRecords() {
		$this->setExpectedException('LogicException', '', 1304785700);

		$uid = uniqid();
		/** @var $fixture t3lib_vfs_Node */
		$fixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array('uid' => $uid)));

		$fixture->setUid(1);
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::setUid
	 */
	public function setUidAlsoSetsUidProperty() {
		$uid = uniqid();

		/** @var $fixture t3lib_vfs_Node */
		$fixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array()));
		$fixture->setUid($uid);
		$this->assertEquals($uid, $fixture->getValue('uid'));
	}

	/**
	 * @test
	 * @covers t3lib_vfs_Node::isNew
	 */
	public function isNewReturnsExpectedValuesForNewAndExistingRecords() {
		/** @var $newNodeFixture t3lib_vfs_Node */
		$newNodeFixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array()));
		/** @var $existingNodeFixture t3lib_vfs_Node */
		$existingNodeFixture = $this->getMockForAbstractClass('t3lib_vfs_Node', array(array('uid' => 1)));

		$this->assertTrue($newNodeFixture->isNew(), 'New Node was not advertised as new.');
		$this->assertFalse($existingNodeFixture->isNew(), 'Existing Node was advertised as new.');
	}
}

?>