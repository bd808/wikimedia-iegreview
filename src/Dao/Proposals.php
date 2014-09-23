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

/**
 * Data access object for proposals.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2014 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Proposals extends AbstractDao {

	/**
	 * @var int|bool $userId
	 */
	protected $userId;


	/**
	 * @param string $dsn PDO data source name
	 * @param string $user Database user
	 * @param string $pass Database password
	 * @param int|bool $uid Authenticated user
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct(
		$dsn, $user, $pass, $uid = false, $logger = null
	) {
		parent::__construct( $dsn, $user, $pass, $logger );
		$this->userId = $uid;
	}

	/**
	 * Save a new proposal.
	 *
	 * @param array $data Proposal data
	 * @return int|bool False if insert fails, proposal id otherwise
	 */
	public function createProposal( array $data ) {
		$data['created_by'] = $this->userId ?: null;
		$cols = array_keys( $data );
		$params = array_map( function ( $elm ) { return ":{$elm}"; }, $cols );
		$sql = self::concat(
			'INSERT INTO proposals (',
			implode( ',', $cols ),
			') VALUES (',
			implode( ',', $params ),
			')'
		);
		return $this->insert( $sql, $data );
	}


	/**
	 * @param array $params
	 * @return object StdClass with rows and found memebers
	 */
	public function search( array $params ) {
		$defaults = array(
			'proposals' => '',
			'sort' => 'id',
			'order' => 'asc',
			'items' => 20,
			'page' => 0,
		);
		$params = array_merge( $defaults, $params );

		$where = array();
		$crit = array();
		$crit['int_userid'] = $this->userId ?: 0;

		$validSorts = array(
			'id', 'title', 'amount', 'theme', 'status',
			'review_count', 'my_review_count',
		);
		$sortby = in_array( $params['sort'], $validSorts ) ?
			$params['sort'] : $defaults['sort'];
		$order = $params['order'] === 'desc' ? 'DESC' : 'ASC';

		if ( $params['items'] == 'all' ) {
			$limit = '';
			$offset = '';
		} else {
			$crit['int_limit'] = (int)$params['items'];
			$crit['int_offset'] = (int)$params['page'] * (int)$params['items'];
			$limit = 'LIMIT :int_limit';
			$offset = 'OFFSET :int_offset';
		}

		$fields = array(
			'p.id as id',
			'p.title as title',
			'p.url as url',
			'p.amount as amount',
			'p.theme as theme',
			'p.status as status',
			'COALESCE(rc.review_count, 0) as review_count',
			'COALESCE(mc.my_review_count, 0) as my_review_count',
		);

		switch( $params['proposals'] ) {
			case 'unreviewed':
				$where[] = 'review_count IS NULL';
				break;
			case 'myqueue':
				$where[] = 'my_review_count IS NULL';
				break;
			default:
				break;
		}

		$reviewCountSql = self::concat(
			'SELECT proposal, count(*) as review_count',
			'FROM reviews'
		);

		$myReviewCountSql = self::concat(
			'SELECT proposal, count(*) as my_review_count',
			'FROM reviews',
			'WHERE reviewer = :int_userid'
		);

		$joins = array(
			"LEFT OUTER JOIN ({$reviewCountSql}) rc on p.id = rc.proposal",
			"LEFT OUTER JOIN ({$myReviewCountSql}) mc on p.id = mc.proposal",
		);

		$sql = self::concat(
			'SELECT SQL_CALC_FOUND_ROWS', implode( ',', $fields ),
			'FROM proposals p',
			$joins,
			self::buildWhere( $where ),
			"ORDER BY {$sortby}, id {$order}",
			$limit, $offset
		);
		return $this->fetchAllWithFound( $sql, $crit );
	}
}