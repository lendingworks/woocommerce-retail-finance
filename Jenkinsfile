pipeline {
	agent none

	options { timeout(time: 30, unit: 'MINUTES') }

	stages {
		stage('Unit tests') {
			agent {
				label 'docker'
			}

			steps {
				// Startup test environment.
				sh 'docker-compose up -d --build'

				// Setup test environment. To load WordPress correctly, the database needs to be available and populated with some specific settings.
				sh 'docker-compose exec -T wordpress bash -c "composer install"'
				sh 'docker-compose exec -T wordpress bash -c "rm -rf /tmp/wordpress-tests-lib/ && ./lendingworks/tests/functional/bin/install-wp-tests.sh rf-woocommerce-test live_usom live_usom db:3360 5.1.1 true"'
				sh 'docker-compose exec -T db bash -c "mysql -h localhost -u live_usom -plive_usom rf-woocommerce < /var/backups/dump.sql"'

				// Run the unit-tests suite.
				sh 'docker-compose exec -T wordpress bash -c "lendingworks/vendor/bin/phpunit -c phpunit.xml --log-junit junit.xml"'
			}

			post {
				always {
					// Tear down containers.
					sh 'docker-compose down'

					junit 'junit.xml'
				}
			}
		}

		stage('Functional tests') {
			agent {
				label 'docker'
			}

			steps {
				// Setup test environment.
				sh 'docker-compose up -d --build'

				// Setup test environment. To load WordPress correctly, the database needs to be available and populated with some specific settings.
				sh 'docker-compose exec -T wordpress bash -c "composer install"'
				sh 'docker-compose exec -T wordpress bash -c "rm -rf /tmp/wordpress-tests-lib/ && ./lendingworks/tests/functional/bin/install-wp-tests.sh rf-woocommerce-test live_usom live_usom db:3360 5.1.1 true"'
				sh 'docker-compose exec -T wordpress bash -c "curl -o ./lendingworks/tests/functional/phpunit https://phar.phpunit.de/phpunit-5.7.27.phar && chmod +x ./lendingworks/tests/functional/phpunit"'
				sh 'docker-compose exec -T db bash -c "mysql -h localhost -u live_usom -plive_usom rf-woocommerce < /var/backups/dump.sql"'

				// Run the functional-tests suite.
				sh 'docker-compose exec -T wordpress bash -c "lendingworks/tests/functional/phpunit -c lendingworks/tests/functional/phpunit.xml.dist"'
			}

			post {
				always {
					// Tear down containers
					sh 'docker-compose down'
				}
			}
		}

		stage('code quality') {
			agent {
				dockerfile {
					additionalBuildArgs '--build-arg PHPVERSION=5.6-fpm'
				}
			}

			steps {
				sh 'composer install'
				sh 'lendingworks/vendor/bin/phpcs --config-set installed_paths lendingworks/vendor/wp-coding-standards/wpcs && lendingworks/vendor/bin/phpcs --standard=WordPress --report=full ./lendingworks/lib'
			}
		}

		stage('deploy') {
			agent {
				label 'docker'
			}

			environment {
				APP_VERSION = sh(
					script: "cat lendingworks/readme.txt | grep 'Stable tag' | cut -d ' ' -f 3",
					returnStdout: true
				).trim()

				BB = credentials("2b723874-a43c-4260-82c7-7db1304b446a")
			}

			steps {
				// Startup test environment.
				sh 'docker-compose up -d --build'

				// Run composer install without dev dependencies to generate an autoloader with path relative to an actual wordpress setup.
				sh 'docker-compose exec -T wordpress bash -c "cd /var/www/wordpress/wp-content/plugins/lendingworks && composer install --no-dev"'

				// something here should be done to deploy to integration server or wherever a development version should go to
				sh 'docker-compose exec -T wordpress bash -c "zip -r lendingworks-${APP_VERSION}.zip lendingworks/lib lendingworks/templates lendingworks/vendor lendingworks/assets lendingworks/lendingworks.php readme.txt"'
			}

			post {
				always {
					// Tear down containers.
					sh 'docker-compose down'

					archiveArtifacts "lendingworks-${APP_VERSION}.zip"
				}

				success {
					sh "curl -u ${BB} -H \"Content-Type: application/json\" -X POST --data '{\"content\": {\"raw\": \":white_check_mark: ${currentBuild.fullDisplayName} build success! [See details](${RUN_DISPLAY_URL})\"}}' https://bitbucket.org/lendingworks/rf-wordpress-woocommerce/pull-requests/${CHANGE_ID}/comments"
				}

				failure {
					sh "curl -u ${BB} -H \"Content-Type: application/json\" -X POST --data '{\"content\": {\"raw\": \":x: ${currentBuild.fullDisplayName} build failed! [See details](${RUN_DISPLAY_URL})\"}}' https://bitbucket.org/lendingworks/rf-wordpress-woocommerce/pull-requests/${CHANGE_ID}/comments"
				}

				unstable {
					sh "curl -u ${BB} -H \"Content-Type: application/json\" -X POST --data '{\"content\": {\"raw\": \":x: ${currentBuild.fullDisplayName} build is unstable! [See details](${RUN_DISPLAY_URL})\"}}' https://bitbucket.org/lendingworks/rf-wordpress-woocommerce/pull-requests/${CHANGE_ID}/comments"
				}
			}
		}

		//stage('deploy to production approval') {
		//	agent {
		//		label 'docker'
		//	}
		//
		//	steps {
		//		// zip content of ./lendingworks excluding all folders and files except lib/, templates/, lendingworks.php and readme.txt.
		//		sh 'zip -r lendingworks.zip lendingworks/lib lendingworks/templates lendingworks.php readme.txt'
		//
		//		// Then upload the archive somewhere (Github release ?)
		//
		//		// Use svn to deploy to WordPress plugins repository
		//		sh 'docker-compose up -d --build'
		//
		//		// UPDATE Stable version in readme.txt from PLUGIN_VERSION
		//
		//		sh 'docker-compose exec -T wordpress bash -c "svn co https://plugins.svn.wordpress.org/lendingworks /var/svn/lendingworks"'
		//		sh 'docker-compose exec -T wordpress bash -c "cp -R /var/www/wordpress/wp-content/plugins/lendingworks/lib/ /var/www/wordpress/wp-content/plugins/lendingworks/templates/ /var/www/wordpress/wp-content/plugins/lendingworks/lendingworks.php /var/www/wordpress/wp-content/plugins/lendingworks/readme.txt /var/svn/lendingworks/trunk/"'
		//		sh 'docker-compose exec -T wordpress bash -c "svn cp trunk tags/PLUGIN_VERSION"'
		//		sh 'docker-compose exec -T wordpress bash -c "svn ci -m 'Initial commit' "'
		//	}
		//
		//	when { branch 'master' }
		//
		//	post {
		//		always {
		//			// Tear down containers.
		//			sh 'docker-compose down'
		//		}
		//	}
		//}
	}
}
