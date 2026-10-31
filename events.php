
<?php
require_once "config/db.php";

// Fetch events from database
$sql = "SELECT * FROM events ORDER BY id DESC";
$result = $conn->query($sql);

$events = [];
$categories = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {

        $events[] = [
            "id" => (int)$row["id"],
            "name" => $row["title"],
            "description" => $row["description"],
            "category" => $row["category"],
            "date" => $row["event_date"],
            "venue" => $row["venue"],
            "status" => $row["status"],
            "poster" => $row["poster"]
        ];

        if (!empty($row["category"])) {
            $categories[] = $row["category"];
        }
    }
}

$categories = array_unique($categories);
sort($categories);
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
        }

        header {
            padding: 20px;
            border-bottom: 1px solid #ccc;
        }

        header h1 {
            margin: 0;
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
        }

        main {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
        }

        .controls {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ccc;
        }

        input, select, button {
            padding: 8px;
            margin: 5px;
            font-size: 14px;
        }

        button {
            cursor: pointer;
        }

        .event {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .event h3 {
            margin-top: 0;
        }

        .event p {
            margin: 5px 0;
        }

        .event-poster {
            width: 220px;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px 0;
            border-radius: 6px;
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
        }

        footer {
            padding: 20px;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            text-align: center;
        }
    </style>
</head>

<body>

    <a href="index.html">Skip to main content</a>

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

                <select id="category" aria-label="Filter events by category">

                    <option value="All">All</option>

                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
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

        // Events received from PHP and MySQL
        let events = <?= json_encode(
            $events,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ) ?>;

        let page = 1;
        let perPage = 5;


        // Safely display text in HTML
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


            // Search events by name
            let result = events.filter(function(event) {

                return event.name.toLowerCase().includes(searchText);

            });


            // Filter by category
            if (category !== "All") {

                result = result.filter(function(event) {

                    return event.category === category;

                });

            }


            // Calculate total pages
            let totalPages = Math.ceil(result.length / perPage);

            if (totalPages === 0) {
                totalPages = 1;
            }

            if (page > totalPages) {
                page = totalPages;
            }


            let start = (page - 1) * perPage;
            let end = start + perPage;

            let pageData = result.slice(start, end);

            let output = "";


            // No events found
            if (pageData.length === 0) {

                output = "<div class='message'>No events found.</div>";

            }


            // Create event cards
            pageData.forEach(function(event) {

                let posterHTML = "";

                if (event.poster) {

                    let posterURL =
                        "uploads/" + encodeURIComponent(event.poster);

                    posterHTML =
                        "<img class='event-poster' src='" +
                        posterURL +
                        "' alt='Event poster' onerror=\"this.outerHTML='<p>Poster Not Available</p>'\">";

                } else {

                    posterHTML = "<p>Poster Not Available</p>";

                }


                let date = new Date(event.date + "T00:00:00");

                let formattedDate = isNaN(date.getTime())
                    ? escapeHTML(event.date)
                    : date.toLocaleDateString("en-GB");


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
                    formattedDate +
                    "</p>" +

                    "<p><strong>Venue:</strong> " +
                    escapeHTML(event.venue) +
                    "</p>" +

                    "<p><strong>Status:</strong> " +
                    escapeHTML(event.status) +
                    "</p>" +

                    "<p>" +
                    escapeHTML(event.description) +
                    "</p>" +

                    (event.status === "Open"
                        ? "<p><a href='register.html'>Register for event</a></p>"
                        : "<p>Registration Closed</p>") +

                    "</article>";

            });


            document.getElementById("output").innerHTML = output;


            // Page information
            document.getElementById("pageInfo").innerHTML =
                "Page " + page + " of " + totalPages;


            // Pagination buttons
            document.getElementById("previousButton").disabled =
                page === 1;

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

                return a.name.localeCompare(b.name);

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


        // Initially display events
        displayEvents();

    </script>

</body>
</html>
