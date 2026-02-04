<?php
/**
 * Created by PhpStorm.
 * User: karg
 * Date: 2019-03-26
 * Time: 14:30
 */

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\QualityCheck\Category;
use App\Entity\QualityCheck\Formula;
use App\Entity\QualityCheck\Ideabox;
use App\Entity\QualityCheck\Question;
use App\Entity\QualityCheck\Questionnaire;
use App\Form\QuestionnaireType;
use App\Repository\CategoryRepository;
use App\Repository\QuestionnaireRepository;
use Doctrine\DBAL\Connection;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/questionnaire", name="admin_questionnaire_")
 * @IsGranted("ROLE_ADMIN")
 */
class QuestionnaireController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function index(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            /** @var QuestionnaireRepository $qr */
            $qr = $this->getDoctrine()->getRepository(Questionnaire::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "delete_questionnaire":
                        $q = $em->find(Questionnaire::class, $request->get('questionnaire_id', null));
                        if ($q->getState() === Questionnaire::STATE_NEW) {
                            $em->remove($q);
                            $em->flush();
                        }
                        break;
                    case "activate_questionnaire":
                        foreach ($qr->findBy(['state' => Questionnaire::STATE_ACTIVE]) as $q) {
                            $q->setState(Questionnaire::STATE_ARCHIVED);
                        }
                        $q = $em->find(Questionnaire::class, $request->get('questionnaire_id', null));
                        $q->setState(Questionnaire::STATE_ACTIVE);
                        $em->flush();
                        break;
                }
            }
            return new JsonResponse($qr->find4Ajax(
                $request->query->getAlnum('sort', 'date'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        return $this->render('admin/questionnaire/index.html.twig', [

        ]);
    }

    /**
     * @Route("/new", name="new")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Throwable
     */
    public function new(Request $request)
    {
        $questionnaire = new Questionnaire();
        $questionnaire->setCreatedBy($this->getUser());
        $questionnaire->setName('Fragebogen ' . \date('Y-m-d'));

        $form = $this->createForm(QuestionnaireType::class, $questionnaire, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $conn->beginTransaction();
            try {
                /** @var Questionnaire $basedOnQuestionaire */
                $basedOnQuestionaire = $form->get('basedOn')->getData();
                if ($basedOnQuestionaire !== null) {
                    $questionnaire->setBasedOn($basedOnQuestionaire);
                    foreach ($basedOnQuestionaire->getCategories() as $category) {
                        $newCategory = new Category();
                        $newCategory->setName($category->getName());
                        $newCategory->setNote($category->getNote());
                        $newCategory->setOrder($category->getOrder());
                        $newCategory->setPrevious($category);
                        $newCategory->setQuestionnaire($questionnaire);
                        $em->persist($newCategory);
                        foreach ($category->getQuestions() as $question) {
                            $newQuestion = new Question();
                            $newQuestion->setCategory($newCategory);
                            $newQuestion->setPrevious($question);
                            $newQuestion->setQuestion($question->getQuestion());
                            $newQuestion->setMiniCheck($question->isMiniCheck());
                            $newQuestion->setMiniCheckInfo($question->getMiniCheckInfo());
                            $newQuestion->setOrder($question->getOrder());
                            $newQuestion->setType($question->getType());
                            $newQuestion->setMasterDataQuestion($question->getMasterDataQuestion());
                            $newQuestion->setSustainable($question->isSustainable());
                            $em->persist($newQuestion);
                            if ($question->getType() === Question::TYPE_NEEDED) {
                                $newFormula = new Formula();
                                $newFormula->setFormulaFalse($question->getFormula()->getFormulaFalse());
                                $newFormula->setFormulaTrue($question->getFormula()->getFormulaTrue());
                                $newFormula->setQuestion($newQuestion);
                                $em->persist($newFormula);
                            }
                            foreach ($question->getIdeaboxes() as $ideabox) {
                                $newIdeabox = new Ideabox();
                                $newIdeabox->setQuestion($newQuestion);
                                $newIdeabox->setPrevious($ideabox);
                                $newIdeabox->setIdea($ideabox->getIdea());
                                $newIdeabox->setOrder($ideabox->getOrder());
                                $newIdeabox->setIdeaboxIcons($ideabox->getIdeaboxIcons());
                                $em->persist($newIdeabox);
                            }
                        }
                        if ($category->getChildren()->count() > 0) {
                            foreach ($category->getChildren() as $child) {
                                $newSubCategory = new Category();
                                $newSubCategory->setParent($newCategory);
                                $newSubCategory->setName($child->getName());
                                $newSubCategory->setOrder($child->getOrder());
                                $newSubCategory->setPrevious($child);
                                $newSubCategory->setQuestionnaire($questionnaire);
                                $em->persist($newSubCategory);
                                foreach ($child->getQuestions() as $question) {
                                    $newQuestion = new Question();
                                    $newQuestion->setCategory($newSubCategory);
                                    $newQuestion->setPrevious($question);
                                    $newQuestion->setQuestion($question->getQuestion());
                                    $newQuestion->setMiniCheck($question->isMiniCheck());
                                    $newQuestion->setMiniCheckInfo($question->getMiniCheckInfo());
                                    $newQuestion->setOrder($question->getOrder());
                                    $newQuestion->setType($question->getType());
                                    $newQuestion->setMasterDataQuestion($question->getMasterDataQuestion());
                                    $newQuestion->setSustainable($question->isSustainable());
                                    $em->persist($newQuestion);
                                    if ($question->getType() === Question::TYPE_NEEDED) {
                                        $newFormula = new Formula();
                                        $newFormula->setFormulaFalse($question->getFormula()->getFormulaFalse());
                                        $newFormula->setFormulaTrue($question->getFormula()->getFormulaTrue());
                                        $newFormula->setQuestion($newQuestion);
                                        $em->persist($newFormula);
                                    }
                                    foreach ($question->getIdeaboxes() as $ideabox) {
                                        $newIdeabox = new Ideabox();
                                        $newIdeabox->setQuestion($newQuestion);
                                        $newIdeabox->setPrevious($ideabox);
                                        $newIdeabox->setIdea($ideabox->getIdea());
                                        $newIdeabox->setOrder($ideabox->getOrder());
                                        $newIdeabox->setIdeaboxIcons($ideabox->getIdeaboxIcons());
                                        $em->persist($newIdeabox);
                                    }
                                }
                            }
                        }
                    }
                }
                $em->persist($questionnaire);
                $em->flush();
                $conn->commit();
                $this->getSuccessMessage();

                return $this->redirectToRoute('admin_questionnaire_home');
            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->getErrorMessage('Beim Hinzufügen des Fragebogens ist ein Fehler aufgetreten!');
                throw $e;
                return $this->redirectToRoute('admin_questionnaire_home');
            }
        }
        return $this->render('admin/questionnaire/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/show/{id}", name="show")
     * @param Request       $request
     * @param Questionnaire $questionnaire
     * @param MenuItem      $menu
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @throws \Throwable
     */
    public function show(Request $request, Questionnaire $questionnaire, MenuItem $menu): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->isXmlHttpRequest()) {
            /** @var CategoryRepository $cr */
            $cr = $this->getDoctrine()->getRepository(Category::class);

            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "up":
                        $cr->up($request->get('category_id', null));
                        break;
                    case "down":
                        $cr->down($request->get('category_id', null));
                        break;
                    case "delete_category":
                        $c = $em->find(Category::class, $request->get('category_id', null));
                        $em->remove($c);
                        $em->flush();
                        break;
                }
                $questionnaire->reorderCategories();
                $em->flush();
            }

            return new JsonResponse($cr->find4Ajax(
                $questionnaire,
                $request->query->getAlnum('sort', 'date'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $menu['admin']['questionnaire']->addChild($questionnaire->getName(), [
            'route' => 'admin_questionnaire_show',
            'routeParameters' => ['id' => $questionnaire->getId()]
        ])
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');

        return $this->render('admin/questionnaire/show.html.twig', [
            'questionnaire' => $questionnaire
        ]);
    }
}
