<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{

    /**
     * Route to the home
     * 
     * @Route("/", name="app_home", methods={"GET"})
     */
    public function index(PostRepository $postRep): Response
    {
        $posts = $postRep->findBy([], ['CreatedAt' => 'DESC']);
        
        return $this->render('post/index.html.twig', compact('posts'));
    }

    /**
     * Used to create a post
     * 
     * @Route("/create", name="app_post_create", methods={"GET","POST"})
     * @IsGranted("POST_CREATE")
     */
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post;
        $postForm = $this->createForm(PostType::class, $post);
        $postForm->handleRequest($request);

        if ($postForm->isSubmitted() && $postForm->isValid()) {
            $post->setUser($this->getUser());
            $em->persist($post);
            $em->flush();
            $this->addFlash('success', 'Your post is successfully created');

            return $this->redirectToRoute("app_home");
        }

        return $this->render(
            'post/create.html.twig', 
            ['form' => $postForm->createView()]
        );
    }

    /**
     * @Route("/post/{id<[0-9]+>}", name="app_post_show", methods={"GET","POST"})
     */
    public function show(Post $post, EntityManagerInterface $em, Request $request, CommentRepository $rep): Response
    {
        $coms = new Comment;
        $comsForm = $this->createForm(CommentType::class, $coms);
        $comsForm->handleRequest($request);

        if ($comsForm->isSubmitted() && $comsForm->isValid()) {
            $coms->setPost($post);
            $coms->setUser($this->getUser());
            $em->persist($coms);
            $em->flush();
            
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render(
            'post/show.html.twig', 
            [
                'post' => $post,
                'form' => $comsForm->createView(),
                'comments' => $rep->findBy(['post' => $post->getId()])
            ]
        );
    }

    /**
     * @Route("/post/{id<[0-9]+>}/edit", name="app_post_edit", methods={"GET","PUT"})
     * @IsGranted("OWNER", subject="post")
     */
    public function edit(Post $post, EntityManagerInterface $em, Request $request): Response
    {
        $form = $this->createForm(PostType::class, $post, ['method' => 'PUT']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Post successfully edited');
            
            return $this->redirectToRoute('app_home');
        }
        return $this->render(
            'post/edit.html.twig', 
            [
                'post' => $post,
                'form' => $form->createView()
            ]
        );
    }

    /**
     * Return a Response object
     * 
     * @Route("/post/{id<[0-9]+>}", name="app_post_delete", methods={"DELETE"})
     * @IsGranted("OWNER", subject="post")
     */
    public function delete(Post $post, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('post_deletion'.$post->getId(), $request->request->get('csrf_token'))) {
            $this->addFlash('success', 'Post successfully deleted');
            $em->remove($post);
            $em->flush();    
        }

        return $this->redirectToRoute('app_home');
    }
}
