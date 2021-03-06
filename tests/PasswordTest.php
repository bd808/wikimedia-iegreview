<?php
/**
 * @section LICENSE
 * This file is part of Wikimedia IEG Grant Review application.
 *
 * Wikimedia IEG Grant Review application is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * Wikimedia IEG Grant Review application is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with Wikimedia IEG Grant Review application.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @file
 * @copyright © 2014 Bryan Davis, Wikimedia Foundation and contributors.
 */

namespace Wikimedia\IEGReview;

/**
 * @coversDefaultClass \Wikimedia\IEGReview\Password
 * @uses \Wikimedia\IEGReview\Password
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2014 Bryan Davis, Wikimedia Foundation and contributors.
 */
class PasswordTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @covers ::encodePassword
	 * @covers ::blowfishSalt
	 */
	public function testUniqueEncoding() {
		$enc = Password::encodePassword( 'password' );
		$enc2 = Password::encodePassword( 'password' );
		$this->assertNotEquals( $enc, $enc2 );
	}

	/**
	 * @covers ::comparePasswordToHash
	 */
	public function testComparePasswordToHash() {
		$enc = Password::encodePassword( 'password' );
		$this->assertTrue( Password::comparePasswordToHash( 'password', $enc ) );
		$this->assertFalse( Password::comparePasswordToHash( 'Password', $enc ) );
	}

	/**
	 * @covers ::randomPassword
	 */
	public function testRandomPassword() {
		// I've always wondered how to write a phpunit test to decide if random is
		// random. For now I'll settle for testing to see if I get the expected
		// number of characters.
		$p = Password::randomPassword( 16 );
		$this->assertEquals( 16, strlen( $p ) );
	}

} //end PasswordTest
