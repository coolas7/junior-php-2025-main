<?php

namespace App\Controller;

use App\Entity\IpInfo;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\IpInfoService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/health', name: 'api_health', methods: ['GET'])]
    #[OA\Get(
        path: "/api/health",
        summary: "Health check",
        tags: ["System"],
        responses: [
            new OA\Response(
                response: 200,
                description: "API is healthy",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "ok")
                    ]
                )
            )
        ]
    )]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }

    #[Route('/ip/{ip}', name: 'api_ip_lookup', methods: ['GET'])]
    #[OA\Get(
        path: "/api/ip/{ip}",
        summary: "Retrieve IP information",
        tags: ["IP Info"],
        parameters: [
            new OA\Parameter(
                name: "ip",
                description: "IP address to lookup",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", example: "134.201.250.155")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Returns IP information",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "ip", type: "string", example: "134.201.250.155"),
                        new OA\Property(property: "type", type: "string", example: "ipv4"),
                        new OA\Property(property: "continent_code", type: "string", example: "NA"),
                        new OA\Property(property: "continent_name", type: "string", example: "North America"),
                        new OA\Property(property: "country_code", type: "string", example: "US"),
                        new OA\Property(property: "country_name", type: "string", example: "United States"),
                        new OA\Property(property: "region_code", type: "string", example: "CA"),
                        new OA\Property(property: "region_name", type: "string", example: "California"),
                        new OA\Property(property: "city", type: "string", example: "Los Angeles"),
                        new OA\Property(property: "zip", type: "string", example: "90001"),
                        new OA\Property(property: "latitude", type: "number", example: 34.05223),
                        new OA\Property(property: "longitude", type: "number", example: -118.24368),
                        new OA\Property(property: "date", type: "string", format: "date-time", example: "2025-09-18 19:21:45")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid IP address format",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid IP address format")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "IP not found",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Retrieves information about a given IP address.
     *
     * @param string $ip The IP address to look up.
     * @param IpInfoService $service The service handling IP operations.
     * @return JsonResponse A JSON response with the IP information or an error message.
     */
    public function getIpInfo(string $ip, IpInfoService $service): JsonResponse
    {
        // Validate the IP address format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(['error' => 'Invalid IP address format'], 400);
        }

        try {
            // Attempt to retrieve IP information
            $ipInfo = $service->getIpInfo($ip);

            // Return the IP information as JSON
            return $this->json([
                'ip' => $ipInfo->getIp(),
                'type' => $ipInfo->getType(),
                'continent_code' => $ipInfo->getContinentCode(),
                'continent_name' => $ipInfo->getContinentName(),
                'country_code' => $ipInfo->getCountryCode(),
                'country_name' => $ipInfo->getCountryName(),
                'region_code' => $ipInfo->getRegionCode(),
                'region_name' => $ipInfo->getRegionName(),
                'city' => $ipInfo->getCity(),
                'zip' => $ipInfo->getZip(),
                'latitude' => $ipInfo->getLatitude(),
                'longitude' => $ipInfo->getLongitude(),
                'date' => $ipInfo->getDate()?->format('Y-m-d H:i:s')
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/ip/{ip}', name: 'api_ip_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/ip/{ip}",
        summary: "Delete IP information",
        tags: ["IP Info"],
        parameters: [
            new OA\Parameter(
                name: "ip",
                in: "path",
                required: true,
                description: "The IP address to delete",
                schema: new OA\Schema(type: "string", example: "134.201.250.155")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "IP successfully deleted",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "IP deleted successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid IP address format",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid IP address format")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "IP not found",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "IP not found")
                    ]
                )
            )
        ]
    )]
    /**
     * Deletes an IP address from the database.
     *
     * @param string $ip The IP address to delete.
     * @param IpInfoService $service The service handling IP operations.
     * @return JsonResponse A JSON response indicating success or failure.
     */
    public function deleteIp(string $ip, IpInfoService $service): JsonResponse
    {
        // Validate the IP address format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(['error' => 'Invalid IP address format'], 400);
        }

        try {
            // Attempt to delete the IP
            $service->deleteIp($ip);
            return $this->json(['message' => "IP $ip deleted successfully"]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }


    // ================================================================
    // Bulk Operations
    // ================================================================


    #[Route('/ip/bulk', name: 'ip_bulk', methods: ['POST'])]
    #[OA\Post(
        path: "/api/ip/bulk",
        summary: "Retrieve info for multiple IP addresses",
        tags: ["Bulk Operations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "ips",
                        type: "array",
                        items: new OA\Items(type: "string", example: "134.201.250.155")
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "IP information results",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "results",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "ip", type: "string", example: "134.201.250.155"),
                                    new OA\Property(property: "type", type: "string", example: "ipv4"),
                                    new OA\Property(property: "continent_code", type: "string", example: "NA"),
                                    new OA\Property(property: "continent_name", type: "string", example: "North America"),
                                    new OA\Property(property: "country_code", type: "string", example: "US"),
                                    new OA\Property(property: "country_name", type: "string", example: "United States"),
                                    new OA\Property(property: "region_code", type: "string", example: "CA"),
                                    new OA\Property(property: "region_name", type: "string", example: "California"),
                                    new OA\Property(property: "city", type: "string", example: "Los Angeles"),
                                    new OA\Property(property: "zip", type: "string", example: "90001"),
                                    new OA\Property(property: "latitude", type: "number", example: 34.05223),
                                    new OA\Property(property: "longitude", type: "number", example: -118.24368),
                                    new OA\Property(property: "date", type: "string", format: "date-time", example: "2025-09-18 19:21:45")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "error",
                            type: "string",
                            example: "Invalid IP address"
                        )
                    ]
                )
            )
        ]
    )]
    /**
     * Bulk retrieve IP information for multiple IP addresses.
     *
     * @param Request $request The HTTP request containing the IPs to look up.
     * @param IpInfoService $service The service handling IP operations.
     * @return JsonResponse A JSON response with the results of the bulk lookup.
     */
    public function bulkGetIpInfo(Request $request, IpInfoService $service): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ips = $data['ips'] ?? [];

        if (!is_array($ips) || empty($ips)) {
            return $this->json(['error' => 'Field "ips" must be a non-empty array'], 400);
        }

        $results = [];
        foreach ($ips as $ip) {
            // Validate the IP address format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $results[] = [
                    'ip' => $ip,
                    'error' => 'Invalid IP address format'
                ];
                continue;
            }

            try {
                $ipInfo = $service->getIpInfo($ip);
                $results[] = [
                    'ip' => $ipInfo->getIp(),
                    'type' => $ipInfo->getType(),
                    'continent_code' => $ipInfo->getContinentCode(),
                    'continent_name' => $ipInfo->getContinentName(),
                    'country_code' => $ipInfo->getCountryCode(),
                    'country_name' => $ipInfo->getCountryName(),
                    'region_code' => $ipInfo->getRegionCode(),
                    'region_name' => $ipInfo->getRegionName(),
                    'city' => $ipInfo->getCity(),
                    'zip' => $ipInfo->getZip(),
                    'latitude' => $ipInfo->getLatitude(),
                    'longitude' => $ipInfo->getLongitude(),
                    'date' => $ipInfo->getDate()?->format('Y-m-d H:i:s')
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'ip' => $ip,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->json(['results' => $results]);
    }

    #[Route('/ip/bulk-delete', name: 'ip_bulk_delete', methods: ['POST'])]
    #[OA\Post(
        path: "/api/ip/bulk-delete",
        summary: "Delete multiple IPs from the database",
        tags: ["Bulk Operations"],
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
                description: "Bulk delete results",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(
                            property: "deleted",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            example: ["134.201.250.155", "131.101.150.139"]
                        ),
                        new OA\Property(
                            property: "errors",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            example: "Invalid IP address format: 131.301.350.132"
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Invalid request",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string", example: "Invalid request")
                    ]
                )
            )
        ]
    )]
    /**
     * Bulk delete IPs from the database.
     *
     * @param Request $request The HTTP request containing the IPs to delete.
     * @param IpInfoService $service The service handling IP operations.
     * @return JsonResponse A JSON response with the results of the bulk delete operation.
     */
    public function bulkDelete(Request $request, IpInfoService $service): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ips = $data['ips'] ?? [];

        if (!is_array($ips) || empty($ips)) {
            return $this->json(['error' => 'Field "ips" must be a non-empty array'], 400);
        }

        $deleted = [];
        $errors = [];

        foreach ($ips as $ip) {
            // Validate the IP address format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $errors[] = "Invalid IP address format: $ip";
                continue;
            }

            try {
                $service->deleteIp($ip);
                $deleted[] = $ip;
            } catch (\RuntimeException $e) {
                $errors[] = "IP not found in database: $ip";
            }
        }

        return $this->json([
            'deleted' => $deleted,
            'errors' => $errors,
        ]);
    }
}
