<?php

namespace App\Controller;

use App\Entity\IpInfo;
use App\Service\BlacklistService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/blacklist')]
class BlacklistController extends AbstractController
{
    #[Route('', name: 'blacklist_add', methods: ['POST'])]
    #[OA\Post(
        path: "/api/blacklist",
        summary: "Add IP to blacklist",
        tags: ["Blacklist"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "ip", type: "string", example: "134.201.250.155")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "IP added to blacklist",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "IP added to blacklist")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid IP address",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid IP address")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "IP not found in database",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP not found in database")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "IP already in blacklist",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP already in blacklist")
                    ]
                )
            )
        ]
    )]
    /**
    * Adds an IP to the blacklist if it exists in the database.
    *
    * @return JsonResponse Success or error message (400/404/409).
    */
    public function add(
        Request $request,
        EntityManagerInterface $em,
        BlacklistService $blacklistService
    ): JsonResponse {
        // Parse JSON body
        $data = json_decode($request->getContent(), true);
        $ip = $data['ip'] ?? null;

        // Validate IP format
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(['error' => "Invalid IP address"], 400);
        }

        $repo = $em->getRepository(IpInfo::class);
        $ipInfo = $repo->findOneBy(['ip' => $ip]);

        // Check if IP info exists in the database
        if (!$ipInfo) {
            return $this->json(['error' => "IP not found in database"], 404);
        }

        // Check if the IP is already in the blacklist
        if ($blacklistService->isBlacklisted($ipInfo)) {
            return $this->json(['error' => "IP $ip is already in blacklist"], 409);
        }

        // Add IP to blacklist
        $blacklistService->addToBlacklist($ipInfo);

        return $this->json(['message' => "IP $ip added to blacklist"]);
    }

    #[Route('/{ip}', name: 'blacklist_remove', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/blacklist/{ip}",
        summary: "Remove IP from blacklist",
        tags: ["Blacklist"],
        parameters: [
            new OA\Parameter(
                name: "ip",
                in: "path",
                description: "IP address to remove from blacklist",
                required: true,
                schema: new OA\Schema(type: "string", example: "134.201.250.155")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "IP removed from blacklist",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "IP removed from blacklist")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "IP not found in blacklist or other blacklist-related error",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP not found in blacklist")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid IP address",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid IP address")
                    ]
                )
            )
        ]
    )]
    /**
    * Removes an IP from the blacklist if it exists.
    *
    * @param string $ip The IP address to remove from the blacklist.
    */
    public function remove(
        string $ip,
        EntityManagerInterface $em,
        BlacklistService $blacklistService
    ): JsonResponse {
        // Validate IP format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(['error' => "Invalid IP address"], 400);
        }

        $repo = $em->getRepository(IpInfo::class);
        $ipInfo = $repo->findOneBy(['ip' => $ip]);

        // Check if IP info exists in the database
        if (!$ipInfo) {
            return $this->json(['error' => "IP not found in database"], 404);
        }

        // Attempt to remove IP from blacklist
        try {
            $blacklistService->removeFromBlacklist($ipInfo);
            return $this->json(['message' => "IP $ip removed from blacklist"]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    // ============================================================================
    // Bulk Operations
    // ============================================================================

    #[Route('/bulk', name: 'blacklist_bulk_add', methods: ['POST'])]
    #[OA\Post(
        path: "/api/blacklist/bulk",
        summary: "Add multiple IPs to blacklist",
        tags: ["Blacklist Bulk"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "ips",
                        type: "array",
                        description: "Array of IP addresses to add to blacklist",
                        items: new OA\Items(type: "string"),
                        example: ["134.201.250.155", "131.101.150.139"]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "IPs processed",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "added",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            example: ["134.201.250.155"]
                        ),
                        new OA\Property(
                            property: "skipped",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            example: ["8.8.8.8"]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request format",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid request format")
                    ]
                )
            )

        ]
    )]
    /**
    * Adds multiple IPs to the blacklist.
    * @return JsonResponse Summary of added and skipped IPs.
    */
    public function bulkAdd(
        Request $request,
        EntityManagerInterface $em,
        BlacklistService $blacklistService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $ips = $data['ips'] ?? [];

        if (!is_array($ips) || empty($ips)) {
            return $this->json(['error' => "Field ips must be a non-empty array"], 400);
        }

        $repo = $em->getRepository(IpInfo::class);

        $added = [];
        // Initialize skipped categories
        $skipped = [
            'not_found' => [],
            'already_blacklisted' => [],
            'invalid_format' => []
        ];

        foreach ($ips as $ip) {
            // Validate IP format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped['invalid_format'][] = $ip;
                continue;
            }

            $ipInfo = $repo->findOneBy(['ip' => $ip]);
            // Check if IP info exists in the database
            if (!$ipInfo) {
                $skipped['not_found'][] = $ip;
                continue;
            }
            // Check if the IP is already in the blacklist
            if ($blacklistService->isBlacklisted($ipInfo)) {
                $skipped['already_blacklisted'][] = $ip;
                continue;
            }

            $blacklistService->addToBlacklist($ipInfo);
            $added[] = $ip;
        }

        return $this->json([
            'added' => $added,
            'skipped' => $skipped
        ]);
    }

    #[Route('/bulk-delete', name: 'blacklist_bulk_remove', methods: ['POST'])]
    #[OA\Post(
        path: "/api/blacklist/bulk-delete",
        summary: "Remove multiple IPs from blacklist",
        tags: ["Blacklist Bulk"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "ips",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["134.201.250.155", "131.101.150.139"]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Bulk remove result",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "removed", type: "array", items: new OA\Items(type: "string")),
                        new OA\Property(
                            property: "skipped",
                            type: "object",
                            properties: [
                                new OA\Property(property: "not_found", type: "array", items: new OA\Items(type: "string")),
                                new OA\Property(property: "not_in_blacklist", type: "array", items: new OA\Items(type: "string"))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request format",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid request format")
                    ]
                )
            )
        ]
    )]
    /**
    * Removes multiple IPs from the blacklist.
    * @return JsonResponse Summary of removed and skipped IPs.
    */
    public function bulkRemove(
        Request $request,
        EntityManagerInterface $em,
        BlacklistService $blacklistService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $ips = $data['ips'] ?? [];

        $removed = [];
        // Initialize skipped categories
        $skipped = [
            'not_found' => [],
            'invalid_format' => [],
            'not_in_blacklist' => []
        ];

        $repo = $em->getRepository(IpInfo::class);

        foreach ($ips as $ip) {
            // Validate IP format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped['invalid_format'][] = $ip;
                continue;
            }

            $ipInfo = $repo->findOneBy(['ip' => $ip]);
            // Check if IP info exists in the database
            if (!$ipInfo) {
                $skipped['not_found'][] = $ip;
                continue;
            }
            // Check if the IP is in the blacklist
            if (!$blacklistService->isBlacklisted($ipInfo)) {
                $skipped['not_in_blacklist'][] = $ip;
                continue;
            }

            $blacklistService->removeFromBlacklist($ipInfo, false);
            $removed[] = $ip;
        }

        $em->flush();

        return $this->json([
            'removed' => $removed,
            'skipped' => $skipped
        ]);
    }
}
