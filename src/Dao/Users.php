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

namespace Wikimedia\IEGReview\Dao;

use \PDOException;
use Wikimedia\IEGReview\Password;

/**
 * Data access object for users.
 * @copyright © 2014 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Users extends AbstractDao {

	public function getUser( $username ) {
		return $this->fetch(
			'SELECT * FROM users WHERE username = ? AND isvalid = 1',
			array( $username )
		);
	}

	public function getUsername( $id ) {
		return $this->fetch(
			'SELECT username FROM users WHERE id = ?',
			array( $id )
		);
	}

	public function getListofUsers( $state ) {
		return $this->fetchAll( 'SELECT * FROM users' );
	}

	public function getUserInfo( $user_id ) {
		return $this->fetch(
			"SELECT * FROM users WHERE id = ?",
			array( $user_id )
		);
	}

	public function isSysAdmin( $user_id ) {
		$res = $this->fetch(
			"SELECT isadmin FROM users WHERE id = ?",
			array( $user_id )
		);
		return $res['isadmin'];
	}

	public function newUserCreate( $answers ) {
		$fields = array(
			'username', 'password', 'email', 'reviewer', 'isvalid',
			'isadmin', 'viewreports'
		);
		$placeholders = array();
		$vals = array();
		foreach ( $fields as $field ) {
			$placeholders[] = ":{$field}";
			$vals[$field] = $answers[$field];
		}

		$sql = 'INSERT INTO users (' .
			implode( ', ', $fields ) . ') VALUES (' .
			implode( ',', $placeholders ) . ')';

		return $this->insert( $sql, $vals );
	}

	/**
	 * @param array $answers Updated user data
	 * @param int $id User id
	 * @return bool True if update suceeded, false otherwise
	 */
	public function updateUserInfo( $answers, $id ) {
		$fields = array(
			'username', 'email', 'reviewer', 'isvalid', 'isadmin',
			'viewreports', 'blocked'
		);
		$placeholders = array();
		foreach ( $fields as $field ) {
			$placeholders[] = "{$field} = :{$field}";
		}

		$sql = self::concat(
			'UPDATE users SET',
			implode( ', ', $placeholders ),
			'WHERE id = :id'
		);
		$stmt = $this->dbh->prepare( $sql );
		$answers['id'] = $id;

		try {
			$this->dbh->beginTransaction();
			$stmt->execute( $answers );
			$this->dbh->commit();
			return true;

		} catch ( PDOException $e) {
			$this->dbh->rollback();
			$this->logger->error( 'Failed to update user', array(
				'method' => __METHOD__,
				'exception' => $e,
				'sql' => $sql,
				'params' => $answers,
			) );
			return false;
		}
	}

	public function updatePassword( $oldpw, $newpw, $id, $force = null ) {
		if ( !$force ) {
			$res = $this->fetch(
				'SELECT password FROM users WHERE id = ?',
				array( $id )
			);

			if ( !Password::comparePasswordToHash( $oldpw, $res['password'] ) ) {
				// passsword doesn't match expected
				$this->logger->notice( 'Invalid old password; will not update', array(
					'method' => __METHOD__,
					'user' => $id,
				) );
				return false;
			}
		}

		$stmt = $this->dbh->prepare( 'UPDATE users SET password = ? WHERE id = ?' );
		try {
			$this->dbh->beginTransaction();
			$stmt->execute( array( Password::encodePassword( $newpw ), $id ) );
			$this->dbh->commit();
			$this->logger->notice( 'Changed password for user', array(
					'method' => __METHOD__,
					'user' => $id,
			) );
			return true;

		} catch ( PDOException $e) {
			$this->dbh->rollback();
			$this->logger->error( 'Failed to update password for user', array(
				'method' => __METHOD__,
				'exception' => $e,
			) );
			return false;
		}
	}

	public function userIsBlocked( $id ) {
		$res = $this->query( "SELECT blocked FROM users WHERE id = ?", array( $id ) );
		return $res['blocked'];
	}

}
