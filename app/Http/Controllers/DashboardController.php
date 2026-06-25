<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use App\Actions\Dashboard\GetOverdueTasks;
use App\Actions\Dashboard\GetUpcomingTasks;
use App\Actions\Dashboard\GetDashboardStats;
use App\Actions\Dashboard\GetRecentCompletions;

readonly class DashboardController
{
    public function __construct(
        private GetDashboardStats $getDashboardStats,
        private GetUpcomingTasks $getUpcomingTasks,
        private GetOverdueTasks $getOverdueTasks,
        private GetRecentCompletions $getRecentCompletions,
        private Factory $view,
    ) {
    }

    public function index(Request $request): View
    {
        $user     = $request->user();
        $today    = today();
        $tomorrow = $today->addDay();

        return $this->view->make(
            'dashboard',
            [
                'stats'             => $this->getDashboardStats->execute($user, $today),
                'upcomingTasks'     => $this->getUpcomingTasks->execute($user, $today, $tomorrow),
                'overdueTasks'      => $this->getOverdueTasks->execute($user, $today),
                'recentCompletions' => $this->getRecentCompletions->execute($user, $today),
                'today'             => $today,
            ]
        );
    }
}
