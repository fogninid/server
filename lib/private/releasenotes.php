<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use OCP\IDBConnection;

/**
 * Class to store release notes
 */
class ReleaseNotes {
	 /** @var \OCP\IDBConnection $dbConnection */
	protected $dbConnection;

	/**
	 * @param OCP\IDBConnection $connection
	 */
	public function __construct(IDBConnection $dbConnection){
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @param string $fromVersion
	 * @param string $toVersion
	 * @return array
	 */
	public function getReleaseNotes($fromVersion, $toVersion){
		$releaseNotes = [];

		try {
			$fromVersionMajorMinor = $this->getMajorMinor($fromVersion);
		} catch (\InvalidArgumentException $e) {
			$fromVersionMajorMinor = '';
		}

		try {
			$toVersionMajorMinor = $this->getMajorMinor($toVersion);
		} catch (\InvalidArgumentException $e) {
			$toVersionMajorMinor = '';
		}

		if ( $fromVersionMajorMinor === '8.2' && $toVersionMajorMinor === '9.0' ) {
			// MySQL only
			if ($this->isMysql()) {
				if ($this->countFilecacheEntries() > 200000) {
					$message = \OC::$server->getL10N('core')->t(
						"You have an ownCloud installation with over 200.000 files so the upgrade might take a while. Hint: You can speed up the upgrade by executing this SQL command manually: ALTER TABLE %s ADD COLUMN checksum varchar(255) DEFAULT NULL AFTER permissions;",
						[$this->dbConnection->getPrefix().'filecache']
					);
					$releaseNotes[] = $message;
				}
			}
		}
		return $releaseNotes;
	}

	/**
	 * @return bool
	 */
	protected function isMysql(){
		return $this->dbConnection->getDatabasePlatform() instanceof MySqlPlatform;
	}

	/**
	 * Count entries in filecache table
	 * @return int
	 */
	protected function countFilecacheEntries(){
		$result = $this->dbConnection->executeQuery("SELECT COUNT(*) FROM *PREFIX*filecache");
		$count = $result->fetchColumn();
		return $count ? $count : 0;
	}

	/**
	 * Strip everything except first digits
	 * @param string $version
	 * @return string
	 */
	private function getMajorMinor($version){
		$versionArray = explode('.', $version);
		if ( count($versionArray)<2 ) {
			throw new \InvalidArgumentException('Version should have at least 2 parts separated by dot.');
		}
		return implode('.', [ $versionArray[0], $versionArray[1] ]);
	}
}
