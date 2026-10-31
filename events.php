
<?php
require_once "config/db.php";

// Fetch events from database
$sql = "SELECT * FROM events ORDER BY id DESC";
$result = $conn->query($sql);

$events = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "id" => $row["id"],
            "name" => $row["title"],
            "description" => $row["description"],
            "category" => $row["category"],
            "date" => $row["event_date"],
            "venue" => $row["venue"],
            "status" => $row["status"],
            "poster" => $row["poster"]
        ];
    }
}

// Get categories dynamically
$categories = [];

foreach ($events as $event) {
    if (!empty($event["category"]) &&
        !in_array($event["category"], $categories)) {
        $categories[] = $event["category"];
    }
}

sort($categories);

// Convert PHP events array into JavaScript
$eventsJson = json_encode(
    $events,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>StudentHub | Events</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            background-color: #f5f7fb;
            color: #222;
        }

        header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            background-color: white;
        }

        header h1 {
            margin: 0;
            color: #173b70;
        }

        nav ul {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        nav a {
            text-decoration: none;
            color: #174ea6;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
        }

        .controls {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: white;
            border-radius: 6px;
        }

        .controls label {
            margin-right: 5px;
        }

        input,
        select,
        button {
            padding: 8px;
            margin: 5px;
            font-size: 14px;
        }

        button {
            cursor: pointer;
        }

        button:hover {
            opacity: 0.85;
        }

        .event {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .event h3 {
            margin-top: 0;
            color: #173b70;
        }

        .event p {
            margin: 7px 0;
        }

        .event-poster {
            width: 220px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 12px 0;
            border-radius: 6px;
            object-fit: cover;
        }

        .event-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            background-color: #e6f4ea;
            color: #137333;
            font-size: 13px;
            font-weight: bold;
        }

        .closed-status {
            background-color: #fce8e6;
            color: #c5221f;
        }

        .register-link {
            display: inline-block;
            padding: 8px 14px;
            margin-top: 8px;
            background-color: #174ea6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .register-link:hover {
            background-color: #123b7d;
        }

        .closed-message {
            color: #c5221f;
            font-weight: bold;
        }

        .pagination {
            margin: 20px 0;
            text-align: center;
        }

        .pagination button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        #pageInfo {
            margin: 0 10px;
            font-weight: bold;
        }

        .message {
            padding: 15px;
            border: 1px solid #ccc;
            margin-top: 15px;
            background-color: white;
            border-radius: 5px;
        }

        footer {
            padding: 20px;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            text-align: center;
            background-color: white;
        }

        @media (max-width: 600px) {
            .controls {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
            }

            .controls input,
            .controls select {
                width: 90%;
            }

            nav ul {
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <a href="#main-content">Skip to main content</a>

    <header>
        <h1>StudentHub</h1>

        <nav aria-label="Primary navigation">
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="events.php" aria-current="page">Events</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
                <li><a href="profile.html">Profile</a></li>
                <li><a href="contact.html">Contact</a></li>
                <li><a href="faq.html">FAQ</a></li>
                <li><a href="feedback.html">Feedback</a></li>
            </ul>
        </nav>
    </header>

    <main id="main-content">

        <nav aria-label="Breadcrumb">
            <ol>
                <li><a href="index.html">Home</a></li>
                <li aria-current="page">Events</li>
            </ol>
        </nav>

        <section>
            <h2>Upcoming Events</h2>

            <div class="controls">

                <label for="search">Search Event:</label>

                <input
                    type="text"
                    id="search"
                    placeholder="Search event"
                    aria-label="Search event"
                >

                <label for="category">Category:</label>

                <select
                    id="category"
                    aria-label="Filter events by category"
                >
                    <option value="All">All</option>

                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>

                </select>

                <button type="button" onclick="sortEvents()">
                    Sort by Name
                </button>

            </div>

            <div id="output">
                Loading events...
            </div>

            <div class="pagination">

                <button
                    type="button"
                    id="previousButton"
                    onclick="previousPage()"
                >
                    Previous
                </button>

                <span id="pageInfo">Page 1</span>

                <button
                    type="button"
                    id="nextButton"
                    onclick="nextPage()"
                >
                    Next
                </button>

            </div>

        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub. All rights reserved.</p>
    </footer>

    <script>

        // Events received from PHP database query
        let events = <?php echo $eventsJson ?: "[]"; ?>;

        let page = 1;
        let perPage = 5;

        // Prevent HTML injection when displaying database values
        function escapeHTML(value) {
            return String(value ?? "").replace(/[&<>"']/g, function(char) {
                return {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#039;"
                }[char];
            });
        }

        // Display events
        function displayEvents() {

            let searchText =
                document.getElementById("search").value.toLowerCase();

            let category =
                document.getElementById("category").value;

            // Search by event name
            let result = events.filter(function(event) {

                return String(event.name || "")
                    .toLowerCase()
                    .includes(searchText);

            });

            // Filter by category
            if (category != "All") {

                result = result.filter(function(event) {

                    return event.category == category;

                });

            }

            // Calculate total pages
            let totalPages = Math.ceil(result.length / perPage);

            if (totalPages == 0) {
                totalPages = 1;
            }

            // Prevent invalid page number
            if (page > totalPages) {
                page = totalPages;
            }

            let start = (page - 1) * perPage;
            let end = start + perPage;

            let pageData = result.slice(start, end);

            let output = "";

            // No events found
            if (pageData.length == 0) {

                output =
                    "<div class='message'>No events found.</div>";

            }

            // Create event cards
            pageData.forEach(function(event) {

                let posterHTML = "";

                if (event.poster) {

                    posterHTML =
                        "<img class='event-poster' src='uploads/" +
                        encodeURIComponent(event.poster) +
                        "' alt='Event poster' onerror=\"this.style.display='none'\">";

                }

                let statusHTML = "";

                if (event.status == "Open") {

                    statusHTML =
                        "<span class='event-status'>Open</span>";

                } else {

                    statusHTML =
                        "<span class='event-status closed-status'>Closed</span>";

                }

                let registrationHTML = "";

                if (event.status == "Open") {

                    registrationHTML =
                        "<p><a class='register-link' href='register.html'>Register for event</a></p>";

                } else {

                    registrationHTML =
                        "<p class='closed-message'>Registration Closed</p>";

                }

                output +=

                    "<article class='event'>" +

                    "<h3>" +
                    escapeHTML(event.name) +
                    "</h3>" +

                    posterHTML +

                    "<p><strong>Category:</strong> " +
                    escapeHTML(event.category) +
                    "</p>" +

                    "<p><strong>Date:</strong> " +
                    escapeHTML(event.date) +
                    "</p>" +

                    "<p><strong>Venue:</strong> " +
                    escapeHTML(event.venue) +
                    "</p>" +

                    "<p><strong>Description:</strong> " +
                    escapeHTML(event.description) +
                    "</p>" +

                    "<p><strong>Status:</strong> " +
                    statusHTML +
                    "</p>" +

                    registrationHTML +

                    "</article>";

            });

            document.getElementById("output").innerHTML = output;

            // Page information
            document.getElementById("pageInfo").innerHTML =
                "Page " + page + " of " + totalPages;

            // Enable / disable pagination buttons
            document.getElementById("previousButton").disabled =
                page == 1;

            document.getElementById("nextButton").disabled =
                page >= totalPages;

        }

        // Search event
        document.getElementById("search").addEventListener(
            "input",
            function() {

                page = 1;
                displayEvents();

            }
        );

        // Filter category
        document.getElementById("category").addEventListener(
            "change",
            function() {

                page = 1;
                displayEvents();

            }
        );

        // Sort events by name
        function sortEvents() {

            events.sort(function(a, b) {

                return String(a.name || "").localeCompare(
                    String(b.name || "")
                );

            });

            page = 1;
            displayEvents();

        }

        // Next page
        function nextPage() {

            page++;
            displayEvents();

        }

        // Previous page
        function previousPage() {

            if (page > 1) {

                page--;
                displayEvents();

            }

        }

        // Display events when page loads
        displayEvents();

    </script>

</body>
</html>
