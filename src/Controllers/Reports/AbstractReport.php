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

namespace Wikimedia\IEGReview\Controllers\Reports;

use Wikimedia\IEGReview\Controller;

/**
 * Base class for building reports.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2014 Bryan Davis, Wikimedia Foundation and contributors.
 */
abstract class AbstractReport extends Controller {

	/**
	 * @return array Column descriptions
	 */
	abstract protected function describeColumns();

	/**
	 * @return stdClass Results object
	 */
	abstract protected function runReport();

	/**
	 * Configure the form.
	 *
	 * The default form will have these members:
	 * - items: number of items per page
	 * - p: page number
	 * - s: sort column
	 * - o: sort order
	 *
	 * Subclasses should call parent::setupForm() before adding their own
	 * additions.
	 */
	protected function setupForm () {
		$this->form->expectInt( 'items',
			array( 'min_range' => 1, 'max_range' => 250, 'default' => 50 )
		);
		$this->form->expectInt( 'p', array( 'min_range' => 0, 'default' => 0 ) );
		$this->form->expectString( 's', array(
			'default' => $this->defaultSortColumn(),
		) );
		$this->form->expectInArray( 'o', array( 'asc', 'desc' ),
			array( 'default' => $this->defaultSortOrder() )
		);
	}

	protected function getTemplate() {
		return 'reports/report.html';
	}

	protected function defaultSortColumn() {
		return 'id';
	}

	protected function defaultSortOrder() {
		return 'asc';
	}

	protected function handleGet() {
		$this->setupForm();
		$this->form->validate( $_GET );

		$this->view->setData( 'columns', $this->describeColumns() );

		$this->view->set( 'items', $this->form->get( 'items' ) );
		$this->view->set( 'p', $this->form->get( 'p' ) );
		$this->view->set( 's', $this->form->get( 's' ) );
		$this->view->set( 'o', $this->form->get( 'o' ) );

		$this->view->setData( 'report', $this->runReport() );
		$this->render( $this->getTemplate() );
	}

}
