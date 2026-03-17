/**
 * Roadmap Tab JavaScript - Interactive Planning & Task Management
 */

jQuery(document).ready(function($) {
    
    // Phase accordion functionality
    $('.wpv-roadmap-phase-header').on('click', function() {
        const phaseId = $(this).data('phase');
        const content = $('#' + phaseId + '-content');
        const toggle = $(this).find('.wpv-roadmap-phase-toggle');
        
        if (content.is(':visible')) {
            content.slideUp(300);
            toggle.text('▶');
        } else {
            content.slideDown(300);
            toggle.text('▼');
        }
    });
    
    // Task checkbox functionality with progress tracking
    $('.wpv-task-checkbox').on('change', function() {
        updateProgressBars();
        saveTaskState();
    });
    
    // Update progress bars based on completed tasks
    function updateProgressBars() {
        // Phase 3.1 - JSON Schema Enhancement
        const phase31Tasks = $('.wpv-roadmap-task input[id^="task-json"], .wpv-roadmap-task input[id^="task-function-detection"], .wpv-roadmap-task input[id^="task-backward"]');
        updatePhaseProgress(phase31Tasks, 0);
        
        // Phase 3.2 - Function-Centric UI
        const phase32Tasks = $('.wpv-roadmap-task input[id^="task-copy"], .wpv-roadmap-task input[id^="task-accordion"], .wpv-roadmap-task input[id^="task-function-actions"]');
        updatePhaseProgress(phase32Tasks, 1);
        
        // Phase 4.1 - Server-Side Progress
        const phase41Tasks = $('.wpv-roadmap-task input[id^="task-progress"], .wpv-roadmap-task input[id^="task-file-counting"], .wpv-roadmap-task input[id^="task-progress-api"]');
        updatePhaseProgress(phase41Tasks, 2);
    }
    
    function updatePhaseProgress(tasks, progressIndex) {
        const total = tasks.length;
        const completed = tasks.filter(':checked').length;
        const percentage = total > 0 ? (completed / total) * 100 : 0;
        
        const progressItem = $('.wpv-progress-item').eq(progressIndex);
        progressItem.find('.wpv-progress-fill').css('width', percentage + '%');
        progressItem.find('.wpv-progress-text').text(completed + '/' + total + ' tasks completed');
        
        // Update progress bar color based on completion
        const progressFill = progressItem.find('.wpv-progress-fill');
        if (percentage === 100) {
            progressFill.css('background', 'linear-gradient(90deg, #46b450 0%, #28a745 100%)');
        } else if (percentage >= 50) {
            progressFill.css('background', 'linear-gradient(90deg, #f56e28 0%, #fd7e14 100%)');
        } else {
            progressFill.css('background', 'linear-gradient(90deg, #2271b1 0%, #46b450 100%)');
        }
    }
    
    // Save task states to localStorage
    function saveTaskState() {
        const taskStates = {};
        $('.wpv-task-checkbox').each(function() {
            taskStates[$(this).attr('id')] = $(this).is(':checked');
        });
        localStorage.setItem('wpv_roadmap_tasks', JSON.stringify(taskStates));
    }
    
    // Load task states from localStorage
    function loadTaskState() {
        const savedStates = localStorage.getItem('wpv_roadmap_tasks');
        if (savedStates) {
            const taskStates = JSON.parse(savedStates);
            Object.keys(taskStates).forEach(function(taskId) {
                $('#' + taskId).prop('checked', taskStates[taskId]);
            });
            updateProgressBars();
        }
    }
    
    // Task hover effects
    $('.wpv-roadmap-task').hover(
        function() {
            $(this).addClass('wpv-task-hover');
        },
        function() {
            $(this).removeClass('wpv-task-hover');
        }
    );
    
    // Architecture item click to copy file path
    $('.wpv-roadmap-arch-item .button-link').on('click', function(e) {
        // Let the VSCode button work normally, but add visual feedback
        $(this).closest('.wpv-roadmap-arch-item').addClass('wpv-arch-clicked');
        setTimeout(() => {
            $(this).closest('.wpv-roadmap-arch-item').removeClass('wpv-arch-clicked');
        }, 200);
    });
    
    // Phase completion celebration
    function checkPhaseCompletion() {
        $('.wpv-progress-item').each(function(index) {
            const progressText = $(this).find('.wpv-progress-text').text();
            if (progressText.includes('3/3 tasks completed')) {
                $(this).addClass('wpv-phase-completed');
                
                // Add celebration effect
                if (!$(this).hasClass('wpv-celebrated')) {
                    $(this).addClass('wpv-celebrated');
                    showCelebration($(this));
                }
            }
        });
    }
    
    function showCelebration($element) {
        // Simple celebration animation
        $element.animate({
            backgroundColor: '#d4edda'
        }, 500).animate({
            backgroundColor: '#fff'
        }, 500);
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + Shift + R to reset all tasks
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
            if (confirm('Reset all task progress? This cannot be undone.')) {
                $('.wpv-task-checkbox').prop('checked', false);
                updateProgressBars();
                saveTaskState();
            }
            e.preventDefault();
        }
        
        // Ctrl/Cmd + Shift + E to expand all phases
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'E') {
            $('.wpv-roadmap-phase-content').slideDown(300);
            $('.wpv-roadmap-phase-toggle').text('▼');
            e.preventDefault();
        }
        
        // Ctrl/Cmd + Shift + C to collapse all phases
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
            $('.wpv-roadmap-phase-content').slideUp(300);
            $('.wpv-roadmap-phase-toggle').text('▶');
            e.preventDefault();
        }
    });
    
    // Task filtering functionality
    function initTaskFiltering() {
        // Add filter buttons (could be added to template later)
        const filterHtml = `
            <div class="wpv-roadmap-filters" style="margin-bottom: 20px;">
                <button class="button wpv-filter-btn" data-filter="all">All Tasks</button>
                <button class="button wpv-filter-btn" data-filter="completed">Completed</button>
                <button class="button wpv-filter-btn" data-filter="pending">Pending</button>
                <button class="button wpv-filter-btn" data-filter="high">High Priority</button>
            </div>
        `;
        
        // Uncomment to add filtering UI
        // $('.wpv-roadmap-main').prepend(filterHtml);
    }
    
    // Export progress functionality
    function exportProgress() {
        const progressData = {
            timestamp: new Date().toISOString(),
            phases: []
        };
        
        $('.wpv-progress-item').each(function(index) {
            const label = $(this).find('label').text();
            const progressText = $(this).find('.wpv-progress-text').text();
            progressData.phases.push({
                name: label,
                progress: progressText
            });
        });
        
        const dataStr = JSON.stringify(progressData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 'wpv-roadmap-progress.json';
        link.click();
        
        URL.revokeObjectURL(url);
    }
    
    // Initialize everything
    loadTaskState();
    updateProgressBars();
    
    // Check for phase completion every time tasks change
    $('.wpv-task-checkbox').on('change', function() {
        setTimeout(checkPhaseCompletion, 100);
    });
    
    // Add tooltip functionality for architecture items
    $('.wpv-roadmap-arch-item').each(function() {
        const $item = $(this);
        const title = $item.find('strong').text();
        $item.attr('title', 'Click VSCode buttons to open files in editor');
    });
    
    // Console log for debugging
    console.log('WP Verifier Roadmap initialized with', $('.wpv-task-checkbox').length, 'tasks');
});

// Additional CSS for hover effects and animations
jQuery(document).ready(function($) {
    // Add dynamic styles
    const dynamicStyles = `
        <style>
        .wpv-task-hover {
            transform: translateX(5px);
            border-color: #2271b1 !important;
        }
        
        .wpv-arch-clicked {
            background: #e3f2fd !important;
            transform: scale(0.98);
        }
        
        .wpv-phase-completed .wpv-progress-fill {
            background: linear-gradient(90deg, #46b450 0%, #28a745 100%) !important;
        }
        
        .wpv-roadmap-filters {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .wpv-filter-btn {
            margin: 0 5px;
        }
        
        .wpv-filter-btn.active {
            background: #2271b1;
            color: #fff;
        }
        </style>
    `;
    
    $('head').append(dynamicStyles);
});