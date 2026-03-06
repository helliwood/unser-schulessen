<?php

namespace App\Controller\SchoolAuthority;

use App\Entity\Survey\Survey;
use App\Entity\Survey\SurveyQuestion;
use App\Form\Survey\SurveyQuestionType;
use App\Repository\Survey\SurveyQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\MenuItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/schultraeger/umfragen')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_SCHOOL_AUTHORITY')]
class SurveyQuestionController extends AbstractController
{
    #[Route(path: '/{id}/fragen', name: 'school_authority_survey_questions')]
    public function questions(Survey $survey, Request $request, MenuItem $menu): Response
    {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isXmlHttpRequest()) {
            /** @var SurveyQuestionRepository $sqr */
            $sqr = $this->getDoctrine()->getRepository(SurveyQuestion::class);
            if ($request->isMethod(Request::METHOD_POST)) {
                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case 'up':
                        $sqr->up($request->get('question_id', null));
                        break;
                    case 'down':
                        $sqr->down($request->get('question_id', null));
                        break;
                    case 'delete_question':
                        $c = $em->find(SurveyQuestion::class, $request->get('question_id', null));
                        if ($c && $c->getSurvey() === $survey) {
                            $em->remove($c);
                            $em->flush();
                        }
                        break;
                }
                $sqr->reorderAll($survey);
                $em->flush();
            }
            return new JsonResponse($sqr->find4Ajax(
                $survey,
                $request->query->get('sort', 'name'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 1)
            ));
        }

        $menu['school_authority_surveys']->addChild($survey->getName() . ': Fragen', [
            'route' => 'school_authority_survey_questions',
            'routeParameters' => ['id' => $survey->getId()]
        ]);

        return $this->render('school_authority/survey/questions.html.twig', [
            'survey' => $survey,
        ]);
    }

    #[Route(path: '/{id}/fragen/add/{type}', name: 'school_authority_survey_questions_add')]
    public function addQuestion(
        Survey $survey,
        string $type,
        Request $request,
        MenuItem $menu,
        EntityManagerInterface $em
    ): Response {
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if ($survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        if (! \in_array($type, \array_keys(SurveyQuestion::TYPE_LABELS), true)) {
            throw new \Exception('Type (' . $type . ') not found!');
        }

        $menu['school_authority_surveys']->addChild($survey->getName() . ': Fragen', [
            'route' => 'school_authority_survey_questions',
            'routeParameters' => ['id' => $survey->getId()]
        ])->addChild('Frage hinzufügen', [
            'route' => 'school_authority_survey_questions_add',
            'routeParameters' => ['id' => $survey->getId(), 'type' => $type]
        ]);

        $question = new SurveyQuestion();
        $question->setType($type);
        $question->setSurvey($survey);
        $question->setOrder($survey->getQuestions()->count() + 1);
        $form = $this->createForm(SurveyQuestionType::class, $question, [
            'type' => $type,
            'surveyState' => $survey->getState(),
            'isNew' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Die Frage wurde gespeichert.');

            return $this->redirectToRoute('school_authority_survey_questions', ['id' => $survey->getId()]);
        }

        return $this->render('school_authority/survey/add_question.html.twig', [
            'question' => $question,
            'form' => $form->createView(),
            'survey' => $survey,
        ]);
    }

    #[Route(path: '/fragen/edit/{id}', name: 'school_authority_survey_questions_edit')]
    public function editQuestion(
        SurveyQuestion $question,
        Request $request,
        MenuItem $menu,
        EntityManagerInterface $em
    ): Response {
        $survey = $question->getSurvey();
        $schoolAuthority = $this->getUser()->getSchoolAuthority();
        if (! $survey || $survey->getSchoolAuthority() !== $schoolAuthority) {
            throw $this->createAccessDeniedException();
        }

        $menu['school_authority_surveys']->addChild($survey->getName() . ': Fragen', [
            'route' => 'school_authority_survey_questions',
            'routeParameters' => ['id' => $survey->getId()]
        ])->addChild($question->getQuestion(), [
            'route' => 'school_authority_survey_questions_edit',
            'routeParameters' => ['id' => $question->getId()]
        ]);

        $form = $this->createForm(SurveyQuestionType::class, $question, [
            'type' => $question->getType(),
            'surveyState' => $survey->getState(),
            'isNew' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Die Frage wurde gespeichert.');
            return $this->redirectToRoute('school_authority_survey_questions', ['id' => $survey->getId()]);
        }

        return $this->render('school_authority/survey/edit_question.html.twig', [
            'question' => $question,
            'form' => $form->createView(),
            'survey' => $survey,
        ]);
    }
}
