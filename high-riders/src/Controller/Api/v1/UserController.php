<?php

namespace App\Controller\Api\v1;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\CommentRepository;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Repository\SpotRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/**
 * @Route("/api/v1/users", name="api_v1_user_", methods={"GET"})
 */
class UserController extends AbstractController
{
    /**
     * Display all spots
     * 
     * URL : /api/v1/users/
     * Road : api_v1_user_index
     * 
     * 
     * @Route("/", name="index")
     */
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->json($users, 200, [], [
            'groups' => ['index_user']
        ]);
    }

    /**
     * Displays the details of a user according to
     * its ID
     * 
     * URL : /api/v1/users/{id}
     * Road : api_v1_user_show
     * 
     * @Route("/{id}", name="show", methods={"GET"}, requirements={"id": "\d+"})
     */
    public function show(User $user): Response
    {
         // If the user does not exist, we return a 404 error
         if (!$user) {
            return $this->json([
                'error' => 'L\'utilisateur n\'existe pas'
            ], 404);
        }
        return $this->json($user, 200, [], [
            'groups' => ['show_user']
        ]);
    }

    /**
     * Add a new user 
     * 
     * URL : /api/v1/users/add
     * Road : api_v1_user_add
     * 
     * @Route("/add", name="add", methods={"POST"})
     */
    public function add(Request $request, UserPasswordHasherInterface $passwordEncoder, SerializerInterface $serialiser, ValidatorInterface $validator): Response
    {
        // We retrieve the JSON
        $jsonData = $request->getContent();

        //  We transform the json into an object : deserialization
        // - We indicate the data to transform (deserialize)
        // - We indicate the format of arrival after conversion (object of type spot)
        // - We indicate the format of departure: we want to pass from json towards an object spot
        $user = $serialiser->deserialize($jsonData, User::class, 'json');

        // We validate the data stored in the $spot object based on
        // on the critieria of the @Assert annotation of the entity 
       

       // If the error array is not empty (at least 1 error)
       // count allows to count the number of elements of an array
       // count([1, 2, 3]) ==> 3
       $errors = $validator->validate($user);

       if(count($errors) > 0){

           // Code 400 : bad request , the data received is not
           // not compliant
           return $this->json($errors, 400);
           
       }

        // $password = $user->get('password')->getData();
        // $user->setPassword($passwordEncoder->hashPassword($user, $password ));
        $user->setPassword(
            $passwordEncoder->hashPassword(
                $user,
                $user->getPassword('password')
            )
        );
    
        // To save, we call the manager
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        // A response is returned indicating that the resource
        // has been created (http code 201)
        return $this->json($user, 201, [], [
                        'groups' => ['add_user'],
                    ]);
       
    }
    
    
    /**
     * Update the user according to
     * its ID
     * 
     * URL : /api/v1/users/{id}
     * Road : api_v1_user_update
     * 
     * @Route("/{id}", name="update", methods={"PUT", "PATCH"}, requirements={"id": "\d+"})
     */
    public function update(int $id, UserRepository $userRepository, User $user, Request $request, SerializerInterface $serialiser)
    {
        // A user is retrieved according to its id
        $user = $userRepository->find($id);
       
        $this->denyAccessUnlessGranted('USER_EDIT', $user, "Vous n'avez pas accés à cette page' !");

        
        // If the user does not exist, we return a 404 error
        if (!$user) {
            return $this->json([
                'error' => 'La user ' . $id . ' n\'existe pas'
            ], 404);
        }
            
            // We retrieve the JSON
            $jsonData = $request->getContent();
            
            // We merge the data from the user with the data
            // from the Front application (insomnia, react, ...)
            // Deserializing in an Existing Object : https://symfony.com/doc/current/components/serializer.html#deserializing-in-an-existing-object
            $user = $serialiser->deserialize($jsonData, User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $user]);
            
            $user->setUpdatedAt(new \DateTimeImmutable());

            // We call the manager to perform the update in DB
            $em = $this->getDoctrine()->getManager();
            
            $em->flush();
            
            return $this->json($user, 200, [], [
                        'groups' => ['show_user'],
                    ]);
             
    }

    /**
     * Delete the user according to
     * its ID
     * 
     * URL : /api/v1/users/{id}
     * Road : api_v1_user_delete
     * 
     * @Route("/{id}", name="delete", methods={"DELETE"}, requirements={"id": "\d+"})
     */
    public function delete(int $id, UserRepository $userRepository,EventRepository $eventRepository,SpotRepository $spotRepository , ParticipationRepository $participationRepository, CommentRepository $commentRepository)
    {
        // A user is retrieved according to its id
        $user = $userRepository->find($id);

        // check for "edit" access: calls all voters
        $this->denyAccessUnlessGranted('USER_DELETE', $user,"Vous n'avez pas accés à cette page' !");

        // If the user does not exist, we return a 404 error
        if (!$user) {
            return $this->json([
                'error' => 'L\'utilisateur ' . $id . ' n\'existe pas'
            ], 404);
        }
        // we get the id of the user 
        $userId=$user->getId();

        // ---ParticipationRepository--
        // the Participation entity is recovered in the form of a table
        $entityParticipation = $participationRepository->findBy(array('user'=>$userId));
        // we get the participations linked to the user to validate the deletion if it exists
        $userParticipation=$user->getParticipations();

        // ---CommentRepository--
        // the Comment entity is recovered in the form of a table
        $entityComment = $commentRepository->findBy(array('user'=>$userId));
        // we get the Comments linked to the user to validate the deletion if it exists
        $userComment=$user->getComment();

        // ---EventRepository--
        // the Event entity is recovered in the form of a table
        $entityEvent = $eventRepository->findBy(array('author'=>$userId));
        // we get the Events linked to the user to validate the deletion if it exists
        $userEvent=$user->getEvents();

         // ---SpotRepository--
        // the Spot entity is recovered in the form of a table
        $entitySpot = $spotRepository->findBy(array('author'=>$userId));
        // we get the Spots linked to the user to validate the deletion if it exists
        $userSpot=$user->getSpots();
       
        // $entityAuthor = $entityEvent[0]->getAuthor();
    //    dd($entityAuthor);
        if ($user!==null) {
            // We call the manager to manage the deletion
            $em = $this->getDoctrine()->getManager();

            if ($userParticipation!==null) {
               
                foreach ($entityParticipation as $idParticipation) {
                    $em->remove($idParticipation);
                }
                $em->flush();
            }
            if ($userComment!==null) {
               
                foreach ($entityComment as $idComment) {
                    $em->remove($idComment);
                }
                $em->flush();
            }
            if ($userEvent!==null) {
               
                foreach ($entityEvent as $idEvent) {
                    $Author=$idEvent->getAuthor();
                    $idAuthor = $Author->getId();
                      dd($idAuthor);
                    //   TODO Voir comment enlever juste author_id sur event
                    $em->remove($idAuthor);
                }
                $em->flush();
            }
            if ($userSpot!==null) {
               
                foreach ($entitySpot as $idSpot) {
                    $em->remove($idSpot);
                }
                $em->flush();
            }
            $em->remove($user);
        
            $em->flush(); // A DELETE SQL query is performed

            return $this->json(['l\'user avec l\'id '. $id . ' à été suprimer'], 203);
        }
    }
    

}
