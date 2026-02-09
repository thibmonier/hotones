<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BlogCategoryRepository;
use App\Repository\BlogPostRepository;
use App\Repository\BlogTagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public blog controller (accessible without authentication).
 */
class BlogController extends AbstractController
{
    private const int POSTS_PER_PAGE = 12;

    public function __construct(
        private readonly BlogPostRepository $postRepository,
        private readonly BlogCategoryRepository $categoryRepository,
        private readonly BlogTagRepository $tagRepository,
    ) {
    }

    /**
     * Blog index page - list all published posts.
     */
    #[Route('/blog', name: 'blog_index', options: ['sitemap' => true], methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $offset = ($page - 1) * self::POSTS_PER_PAGE;

        $posts = $this->postRepository->findPublishedPublic(limit: self::POSTS_PER_PAGE, offset: $offset);

        $totalPosts = $this->postRepository->countPublishedPublic();
        $totalPages = (int) ceil($totalPosts / self::POSTS_PER_PAGE);

        // Sidebar data
        $categories  = $this->categoryRepository->findActiveWithPublishedPosts();
        $popularTags = $this->tagRepository->findPopular(limit: 10);
        $recentPosts = $this->postRepository->findRecentPublishedPublic(limit: 5);

        return $this->render('blog/index.html.twig', [
            'posts'       => $posts,
            'categories'  => $categories,
            'popularTags' => $popularTags,
            'recentPosts' => $recentPosts,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
        ]);
    }

    /**
     * Single blog post page.
     */
    #[Route('/blog/{slug}', name: 'blog_show', options: ['sitemap' => true], methods: ['GET'])]
    public function show(string $slug): Response
    {
        $post = $this->postRepository->findPublishedBySlugPublic($slug);

        if ($post === null) {
            throw $this->createNotFoundException('Article non trouvé ou non publié.');
        }

        // Related posts (same category)
        $relatedPosts = $this->postRepository->findRelatedPostsPublic($post, limit: 3);

        // Sidebar data
        $categories  = $this->categoryRepository->findActiveWithPublishedPosts();
        $popularTags = $this->tagRepository->findPopular(limit: 10);
        $recentPosts = $this->postRepository->findRecentPublishedPublic(limit: 5);

        return $this->render('blog/show.html.twig', [
            'post'         => $post,
            'relatedPosts' => $relatedPosts,
            'categories'   => $categories,
            'popularTags'  => $popularTags,
            'recentPosts'  => $recentPosts,
        ]);
    }

    /**
     * Blog posts filtered by category.
     */
    #[Route('/blog/category/{slug}', name: 'blog_category', options: ['sitemap' => true], methods: ['GET'])]
    public function category(string $slug): Response
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if ($category === null) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }

        $posts = $this->postRepository->findByCategoryPublic($category);

        // Sidebar data
        $categories  = $this->categoryRepository->findActiveWithPublishedPosts();
        $popularTags = $this->tagRepository->findPopular(limit: 10);
        $recentPosts = $this->postRepository->findRecentPublishedPublic(limit: 5);

        return $this->render('blog/category.html.twig', [
            'category'    => $category,
            'posts'       => $posts,
            'categories'  => $categories,
            'popularTags' => $popularTags,
            'recentPosts' => $recentPosts,
        ]);
    }

    /**
     * Blog posts filtered by tag.
     */
    #[Route('/blog/tag/{slug}', name: 'blog_tag', options: ['sitemap' => true], methods: ['GET'])]
    public function tag(string $slug): Response
    {
        $tag = $this->tagRepository->findBySlug($slug);

        if ($tag === null) {
            throw $this->createNotFoundException('Tag non trouvé.');
        }

        $posts = $this->postRepository->findByTagPublic($tag);

        // Sidebar data
        $categories  = $this->categoryRepository->findActiveWithPublishedPosts();
        $popularTags = $this->tagRepository->findPopular(limit: 10);
        $recentPosts = $this->postRepository->findRecentPublishedPublic(limit: 5);

        return $this->render('blog/tag.html.twig', [
            'tag'         => $tag,
            'posts'       => $posts,
            'categories'  => $categories,
            'popularTags' => $popularTags,
            'recentPosts' => $recentPosts,
        ]);
    }
}
