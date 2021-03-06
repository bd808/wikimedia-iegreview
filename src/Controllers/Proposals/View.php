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

namespace Wikimedia\IEGReview\Controllers\Proposals;

use Wikimedia\IEGReview\Controller;

/**
 * View a proposal.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2014 Bryan Davis, Wikimedia Foundation and contributors.
 */
class View extends Controller {

	/**
	 * @var \Wikimedia\IEGReview\Dao\AbstractDao $dao
	 */
	protected $reviewsDao;

	public function setReviewsDao( $dao ) {
		$this->reviewsDao = $dao;
	}

	protected function handleGet( $id ) {
		$proposal = $this->dao->getProposal( $id );
		$this->view->setData( 'proposal', $proposal );

		if ( $this->authManager->isReviewer() ) {
			$myReview = $this->reviewsDao->reviewByUser( $id );
			if ( $myReview ) {
				$this->view->setData( 'myreview', $myReview );
			} else {
				$this->view->setData( 'myreview', array() );
			}

			// Reviewers can only see reviews after they have reviewed
			// the proposal themselves or if they are also an admin
			if ( $myReview || $this->authManager->isAdmin() ) {
				$this->addReviewsToView( $id );
			}
		} else {
			// Non-reviewers always see reviews. The template is responsible for
			// showing/hiding information based on the user's permissions.
			$this->addReviewsToView( $id );
		}

		$this->render( 'proposals/view.html' );
	}

	protected function addReviewsToView( $id ) {
		$reviews = $this->reviewsDao->getReviews( $id );
		$this->view->setData( 'reviews', $reviews );
	}
}
