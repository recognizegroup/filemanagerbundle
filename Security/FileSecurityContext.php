<?php
namespace Recognize\FilemanagerBundle\Security;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Repository\DirectoryRepository;
use Recognize\FilemanagerBundle\Utils\PathUtils;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Checks access for certain actions on directories
 *
 * Class FilePermissionService
 * @package Recognize\FilemanagerBundle\Service
 */
class FileSecurityContext implements FileSecurityContextInterface {

    private $config;
    private $always_authenticate = false;

    private $acl_provider;
    private $authorization_checker = null;

    private $roles = array();

    /**
     * @var FilePermissionCache $permission_cache
     */
    private $permission_cache;

    /**
     * @var DirectoryRepository
     */
    protected $directoryRepository;

    /**
     * @param array $configuration
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function __construct( array $configuration, AclProviderInterface $aclprovider,
                                 TokenStorageInterface $tokenstorage, DirectoryRepository $directoryRepository,
                                 $always_authenticate = false ) {
        $this->config = $configuration;
        $this->always_authenticate = $always_authenticate;
        if( !$always_authenticate ){
            if( array_key_exists( 'security', $configuration ) == false ){
                $this->always_authenticate = true;
            }
        }

        $this->directoryRepository = $directoryRepository;
        $this->acl_provider = $aclprovider;;
        $this->permission_cache = new FilePermissionCache();

        $token = $tokenstorage->getToken();
        $securityidentities = array();
        $roles = array();
        if( $token !== null ) {

            // Get the security identities of the currently logged in user
            // Get the user first so it gets checked before the roles
            try {
                $user = UserSecurityIdentity::fromToken($token);
                $securityidentities[] = $user;
            } catch( \Exception $e ){
            }

            // Get the roles
            $roles = $token->getUser()->getRoles();
            for ($i = 0, $length = count($roles); $i < $length; $i++) {
                $securityidentities[] = new RoleSecurityIdentity($roles[$i]);
            }
        }

        $this->roles = $roles;
        $this->securityidentities = $securityidentities;
    }

    /**
     * Check if an action is granted
     *
     * @param string $action                   The action to check
     * @param string $working_directory        The working direcotry
     * @param string $relativepath             The relative path from the working directory
     * @return boolean
     */
    public function isGranted( $action, $working_directory, $relativepath = "" ){

        if( $this->always_authenticate == false ){
            $absolute_path = PathUtils::addTrailingSlash( $working_directory ) . PathUtils::addTrailingSlash( $relativepath );
            $directory_relativepath = PathUtils::removeFirstSlash( PathUtils::moveUpPath( $relativepath ) );
            $directory_name = PathUtils::getLastNode( $relativepath );

            // Utilize the cache if the path is set
            if( $this->permission_cache->isCached($action, $absolute_path) ){
                return $this->permission_cache->isGranted($action, $absolute_path );

            // Otherwise check the permissions
            } else {

                if( count( $this->securityidentities ) !== 0 ){

                    // Get the directory object from the database
                    $results = $this->directoryRepository->findDirectoryByLocation( $working_directory, $directory_relativepath, $directory_name );
                    if( count($results) > 0 ){
                        $directoryobject = $results[ 0 ];

                    } else {
                        $directoryobject = $this->directoryRepository->getEmptyDirectory( $working_directory,
                            $directory_relativepath, $directory_name );
                    }

                    return $this->isActionGrantedForDirectory( $action, $directoryobject );


                // Non logged in users DONT get access
                } else {
                    $this->permission_cache->stagePath( $absolute_path );
                    $this->permission_cache->commitResultsForStagedPaths( $action, false );
                    return false;
                }
            }
        } else {
            return true;
        }
    }

    /**
     * Check if the action is granted for the directory using the ACLs and the configuration security
     *
     * @param string $action                      The action to check
     * @param Directory $directory                The directory object
     * @return bool
     */
    protected function isActionGrantedForDirectory( $action, Directory $directory ){
        // Make sure to run the directories through the database ACLs first
        try {
            $acl = $this->getDirectoryACLs( $directory, $this->securityidentities );

            // Check the ACLs that are found
            $actionmasks = array( DirectoryMaskBuilder::getMaskFromValues( array( $action ) ) );
            $granted = $acl->isGranted( $actionmasks, $this->securityidentities );

        // If no ACLs could be found for the directory, apply the YAML based security
        } catch( \Exception $e ){
            if( $this->authorization_checker == null ){
                $this->authorization_checker = new ConfigurationAuthorizationChecker(
                    $this->config['access_control'] );
            }
            $this->authorization_checker->setCurrentRoles( $this->roles );


            // Set the working directory name which is used in the access lists
            $directory->setWorkingDirectoryName( "default" );
            if( isset( $this->config['directories'] ) ){
                foreach( $this->config['directories'] as $working_directory_name => $directorypath ){
                    if( $directorypath == $directory->getWorkingDirectory() ){
                        $directory->setWorkingDirectoryName( $working_directory_name );
                        break;
                    }
                }
            }
            $granted = $this->authorization_checker->isGranted( $action, $directory );
        }

        // Cache the result
        $this->permission_cache->commitResultsForStagedPaths( $action, $granted );
        return $granted;
    }

    /**
     * Recursively go up the directory structure until a directory with ACLs
     * for the current security identities is found
     *
     * @param Directory $directory
     * @param SecurityIdentityInterface[] $securityidentities
     * @return AclInterface
     *
     * @throws AclNotFoundException
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    protected function getDirectoryACLs( Directory $directory, $securityidentities ){
        $this->permission_cache->stagePath( PathUtils::addTrailingSlash( $directory->getAbsolutePath() ) );

        if( $directory->getId() != null && $directory->getId() != 0 ){
            $domainidentity = ObjectIdentity::fromDomainObject( $directory );

            try {
                return $this->acl_provider->findAcl( $domainidentity, $securityidentities );
            } catch( AclNotFoundException $e ){
                return $this->getParentDirectoryACLs( $directory, $securityidentities );
            }
        } else {
            return $this->getParentDirectoryACLs( $directory, $securityidentities );
        }
    }

    /**
     * Get the parent directory and check if it has ACLs
     *
     * @param Directory $directory
     * @param SecurityIdentityInterface[] $securityidentities
     * @return AclInterface
     *
     * @throws AclNotFoundException
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    protected function getParentDirectoryACLs( Directory $directory, $securityidentities ){

        // Check if we are in the working directory
        if( ( $directory->getAbsolutePath() ) != PathUtils::removeMultipleSlashes( $directory->getWorkingDirectory() ) ){

            $parent_directory = $directory->getParentDirectory();
            if( $parent_directory == null ){
                $parent_directory = $this->directoryRepository->findParentDirectory( $directory->getWorkingDirectory(),
                    $directory->getRelativePath() );
            }

            return $this->getDirectoryACLs( $parent_directory, $securityidentities );

        } else {
            throw new AclNotFoundException();
        }
    }
}
