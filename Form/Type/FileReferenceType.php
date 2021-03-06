<?php
namespace Recognize\FilemanagerBundle\Form\Type;

use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Form\DataTransformer\FileToPathTransformer;
use Recognize\FilemanagerBundle\Repository\DirectoryRepository;
use Recognize\FilemanagerBundle\Repository\FileRepository;
use Recognize\FilemanagerBundle\Response\FileChanges;
use Recognize\FilemanagerBundle\Service\FiledataSynchronizer;
use Recognize\FilemanagerBundle\Service\FiledataSynchronizerInterface;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Utils\PathUtils;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;


class FileReferenceType extends AbstractType {

    /**
     * @var FilemanagerService $filemanager
     */
    private $filemanager;

    /**
     * @var FiledataSynchronizerInterface
     */
    private $synchronizer;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param FilemanagerService $service
     * @param FileRepository $fileRepository
     */
    public function __construct( FilemanagerService $service, FiledataSynchronizerInterface $synchronizer, TranslatorInterface $translator ){
        $this->filemanager = $service;
        $this->synchronizer = $synchronizer;
        $this->translator = $translator;
    }

    /**
     * Allow both UploadedFiles and path strings
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ( $options ) {
            $data = $event->getData();

            // Ignore empty values
            if ($data !== null && $data !== "") {
                try {
                    $fileref = null;

                    // Allow UploadFile for fallback when there is no javascript
                    if ($data instanceof UploadedFile) {
                        /** @var FileChanges $changes */
                        $changes = $this->filemanager->saveUploadedFile($data, $options['directory'] . $data->getClientOriginalName(), true);
                        $fileref = $this->synchronizer->loadFileReference($this->filemanager->getWorkingDirectory(), $changes->getFile()->getRelativePath() . $changes->getFile()->getFilename());

                    // Allow a relative path to the file as well
                    } else if (is_string($data)) {

                        $directory = PathUtils::removeFirstSlash( PathUtils::moveUpPath( $data ) );
                        $filename = "/^" . preg_quote( PathUtils::getLastNode($data), "/" ) . "$/";
                        $files = $this->filemanager->searchDirectoryContents($directory, $filename, true );
                        if( count($files) > 0){
                            $fileref = $this->synchronizer->loadFileReference($this->filemanager->getWorkingDirectory(), $files[0]->getRelativePath() . $files[0]->getFilename());
                        }
                    }

                    if( $fileref == null ){
                        throw new \RuntimeException();
                    } else {
                        $event->setData($fileref);
                    }

                } catch(\RuntimeException $e) {
                    $event->setData(null);

                    // Add the translation here because Symfony REFUSES to create proper documentation about
                    // where to merge options to add a proper validation constraint by default
                    $event->getForm()->addError( new FormError( $this->translator->trans("File not found in the database", array(), "validators") ) );
                }
            }
        });

        $builder->addViewTransformer(new FileToPathTransformer());
    }

    /**
     * Add previews and the database ID to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options) {
        $view->vars['multipart'] = true;

        $value = $view->vars['data'];
        if( $value instanceof FileReference ){
            $view->vars['preview'] = "/admin/fileapi/preview?filemanager_path=" . $value->getRelativePath() . $value->getFilename();
            $view->vars['value'] = $value->getFilename();
        }

        $view->vars['is_simple'] = $options['is_simple'];
        $view->vars['directory'] = $options['directory'];
        $view->vars['reload_directory_from_localstorage'] = $options['reload_directory_from_localstorage'];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver){
        $resolver->setDefaults(array(
            'is_simple' => false,
            'reload_directory_from_localstorage' => false,
            'directory' => ""
        ));

        $resolver->setAllowedTypes(array( 'is_simple' => array("bool") ));
        $resolver->setAllowedTypes(array( 'reload_directory_from_localstorage' => array("bool") ));
        $resolver->setAllowedTypes(array( 'directory' => array("string") ));
    }


    public function getParent(){
        return "file";
    }

    public function getName(){
        return "filereference";
    }

}