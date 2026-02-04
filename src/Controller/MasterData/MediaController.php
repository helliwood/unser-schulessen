<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2020-01-13
 * Time: 10:20
 */

namespace App\Controller\MasterData;

use App\Controller\AbstractController;
use App\Entity\Media;
use App\Form\DirectoryType;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Exception;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/master_data/media", name="master_data_media_")
 * @IsGranted("ROLE_USER")
 */
class MediaController extends AbstractController
{
    /**
     * @Route("/{id}", name="home", defaults={"id"=null}, requirements={"id"="[0-9]*"})
     * @param MenuItem   $menu
     * @param Request    $request
     * @param Media|null $media
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function index(MenuItem $menu, Request $request, ?Media $media)
    {
        /** @var MediaRepository $rr */
        $mr = $this->getDoctrine()->getRepository(Media::class);

        if ($request->isXmlHttpRequest()) {
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "delete":
                        /** @var Media $m */
                        $m = $em->find(Media::class, $request->request->get('id', null));
                        if ($this->getUser()->getCurrentSchool() === $m->getSchool() &&
                            ($this->isGranted('ROLE_FOOD_COMMISSIONER') ||
                                $this->isGranted('ROLE_SCHOOL_AUTHORITIES_ACTIVE'))) {
                            $file = $this->getParameter('documents_directory') . '/' .
                                $m->getSchool()->getId() . '/' . $m->getId();
                            if (\is_file($file)) {
                                \unlink($file);
                            }
                            $em->remove($m);
                            $em->flush();
                        }
                        break;
                }
            }

            if (! \is_null($media)) {
                $media = $mr->findOneBy(['id' => $media]);
            }

            return new JsonResponse($mr->find4Ajax(
                $this->getUser()->getCurrentSchool(),
                $request->query->get('sort', 'fileName'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1),
                $media
            ));
        }

        $menu['master_data_media']->addChild('Neues Dokument', [
            'route' => 'master_data_media_file_new'
        ]);

//        if ($media) {
//            $menu['master_data_media']->addChild($media->getFileName(), [
//                'route' => 'master_data_media_home',
//                'routeParameters' => ['id' => $media->getId()]
//            ])->setCurrent(true);
////            dd($menu);
//        }

        return $this->render('master_data/media/index.html.twig', [
            'school' => $this->getUser()->getCurrentSchool(),
            'parent' => $media,
        ]);
    }


    /**
     * @Route("/file/new/{id}", name="file_new", defaults={"id"=null}, requirements={"id"="[0-9]*"})
     * @Security("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param MenuItem   $menu
     * @param Request    $request
     * @param Media|null $mediasParent
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function new(MenuItem $menu, Request $request, ?Media $mediasParent = null)
    {
        $school = $this->getUser()->getCurrentSchool();

        $menu['master_data_media']->addChild('Neues Dokument', [
            'route' => 'master_data_media_file_new'
        ]);

        $em = $this->getDoctrine()->getManager();

        $media = new Media();
        $media->setSchool($school);
        $media->setCreatedBy($this->getUser());

        if ($mediasParent) {
            $media->setParent($mediasParent);
        }

        $form = $this->createForm(MediaType::class, $media);
        $form->handleRequest($request);

        if ($request->request->has('cancel')) {
            return $this->redirectToRoute('master_data_home');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form['file']->getData();
            $media->setFileSize($file->getSize());
            $media->setMimeType($file->getMimeType());
            $media->setFileName(\pathinfo($file->getClientOriginalName(), \PATHINFO_BASENAME));
            $em->persist($media);
            $em->flush();

            if (! \is_dir($this->getParameter('documents_directory'))) {
                \mkdir($this->getParameter('documents_directory'));
            }
            if (! \is_dir($this->getParameter('documents_directory') . '/' . $school->getId())) {
                \mkdir($this->getParameter('documents_directory') . '/' . $school->getId());
            }
            try {
                $file->move($this->getParameter('documents_directory') . '/' . $school->getId(), $media->getId());
            } catch (FileException $e) {
                throw $e;
            }

            $this->getSuccessMessage('Das Dokument wurde erfolgreich gespeichert!');

            $mediasParentId = $mediasParent ? $mediasParent->getId() : null;
            return $this->redirectToRoute('master_data_media_home', ['id' => $mediasParentId]);
        }

        return $this->render('master_data/media/new.html.twig', [
            'form' => $form->createView(),
            'parent' => $mediasParent
        ]);
    }

    /**
     * @Route("/directory/new/{id}", name="directory_new", defaults={"id"=null}, requirements={"id"="[0-9]*"})
     * @Security("is_granted('ROLE_FOOD_COMMISSIONER') or is_granted('ROLE_SCHOOL_AUTHORITIES_ACTIVE')")
     * @param MenuItem   $menu
     * @param Request    $request
     * @param Media|null $mediasParent
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function directoryNew(MenuItem $menu, Request $request, ?Media $mediasParent)
    {
        $school = $this->getUser()->getCurrentSchool();

        $menu['master_data_media']->addChild('Neues Verzeichnis', [
            'route' => 'master_data_media_directory_new'
        ]);

        $em = $this->getDoctrine()->getManager();

        $media = new Media();
        $media->setSchool($school);
        $media->setCreatedBy($this->getUser());
        $media->setDirectory(true);

        if ($mediasParent) {
            $media->setParent($mediasParent);
        }

        $form = $this->createForm(DirectoryType::class, $media);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($media);
            $em->flush();

            if (! \is_dir($this->getParameter('documents_directory'))) {
                \mkdir($this->getParameter('documents_directory'));
            }
            if (! \is_dir($this->getParameter('documents_directory') . '/' . $school->getId())) {
                \mkdir($this->getParameter('documents_directory') . '/' . $school->getId());
            }

            $this->getSuccessMessage('Das Verzeichnis wurde erfolgreich erstellt!');

            $mediasParentId = $mediasParent ? $mediasParent->getId() : null;
            return $this->redirectToRoute('master_data_media_home', ['id' => $mediasParentId]);
        }

        return $this->render('master_data/media/directory.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/download/{id}", name="download")
     * @param Media $media
     * @return BinaryFileResponse
     * @throws Exception
     */
    public function download(Media $media): BinaryFileResponse
    {
        $file = $this->getParameter('documents_directory') . '/' .
            $media->getSchool()->getId() . '/' . $media->getId();
        if (\is_file($file) && $this->getUser()->getCurrentSchool() === $media->getSchool()) {
            $response = new BinaryFileResponse($file);
            $response->headers->set('Content-Type', $media->getMimeType());
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $media->getFileName());
            return $response;
        }
        throw $this->createNotFoundException();
    }
}
