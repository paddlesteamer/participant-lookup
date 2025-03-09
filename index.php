<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITRA Sorgulama</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .form-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        #urlInput {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 300px; /* Set a fixed width for the input */
            margin-right: 10px; /* Space between input and button */
        }

        #fetchButton {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #fetchButton:hover {
            background-color: #2980b9; /* Darker shade on hover */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #3498db;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e0e0e0;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid #3498db;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Add styles for the sortable icon */
        .sortable-icon {
            margin-left: 5px;
            font-size: 0.8em;
            color: #fff;
            display: none; /* Initially hidden */
        }
    </style>
    <script>
        let sortingEnabled = false; // Flag to control sorting

        async function fetchParticipants() {
            const tableBody = document.getElementById('participantsTableBody');
            tableBody.innerHTML = ''; // Clear the table before fetching new participants

            const raceUrl = document.getElementById('urlInput').value; // Get URL from input field

            const response = await fetch('parse.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ url: raceUrl }),
            });

            const data = await response.json();
            displayParticipants(data.participants);
        }

        async function displayParticipants(participants) {
            const tableBody = document.getElementById('participantsTableBody');
            tableBody.innerHTML = '';

            // Populate the table with participants' basic information and show loading spinner for ITRA
            participants.forEach(participant => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="name-cell">${participant.name}</td>
                    <td>${participant.gender}</td>
                    <td class="age-group-cell"><div class="spinner"></div></td>
                    <td>${participant.team}</td>
                    <td class="itra-cell"><div class="spinner"></div></td> <!-- Show loading spinner -->
                `;
                tableBody.appendChild(row);
            });

            // Process participants in batches of 10
            const batchSize = 10;
            let currentIndex = 0;

            async function processNextBatch() {
                const batch = participants.slice(currentIndex, currentIndex + batchSize);
                if (batch.length === 0) return;

                // Create promises for current batch
                const batchPromises = batch.map(async (participant, batchIndex) => {
                    const absoluteIndex = currentIndex + batchIndex;
                    try {
                        const itraResponse = await fetch('query.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ name: participant.name }),
                        });
                        const itraData = await itraResponse.json();
                        
                        // Update cell as soon as we get the result
                        const itraCell = tableBody.rows[absoluteIndex].querySelector('.itra-cell');
                        itraCell.innerHTML = itraData.pi;

                        const ageGroupCell = tableBody.rows[absoluteIndex].querySelector('.age-group-cell');
                        ageGroupCell.innerHTML = itraData.ageGroup;

                        const nameCell = tableBody.rows[absoluteIndex].querySelector('.name-cell');
                        nameCell.innerHTML = '<a href="https://itra.run/RunnerSpace/' + itraData.runnerId + '">' + participant.name + '</a>';

                    } catch (error) {
                        console.error(`Error fetching ITRA for ${participant.name}:`, error);
                        const itraCell = tableBody.rows[absoluteIndex].querySelector('.itra-cell');
                        itraCell.innerHTML = 'Error';
                    }
                });

                // Process current batch and move to next
                await Promise.all(batchPromises);
                currentIndex += batchSize;
                processNextBatch(); // Process next batch
            }

            // Start processing the first batch
            await processNextBatch();

            // Enable sorting and show sortable icon after all batches are processed
            sortingEnabled = true;
            const headers = document.querySelectorAll('th');
            headers.forEach(header => {
                const icon = document.createElement('span');
                icon.className = 'sortable-icon';
                icon.innerHTML = '🔼'; // Sortable icon (up arrow)
                header.appendChild(icon);
                header.style.cursor = 'pointer'; // Change cursor to pointer
            });
        }

        function sortTable(columnIndex) {
            if (!sortingEnabled) return; // Prevent sorting if not enabled

            const table = document.getElementById("participantsTable");
            const rows = Array.from(table.rows).slice(1);
            const isAscending = table.getAttribute('data-sort') === 'asc';

            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].innerText;
                const bText = b.cells[columnIndex].innerText;
                return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });

            rows.forEach(row => table.appendChild(row));
            table.setAttribute('data-sort', isAscending ? 'desc' : 'asc');
        }
    </script>
</head>
<body>
    <h1>ITRA Sorgulama</h1>
    <p>ITRA numarasını sorgulamak için bir yarışmanın parkur URL'ini veya yarışmacı listesinin
        yayınlandığı AppHurra linkini gir. Şimdilik sadece AppHurra üzerinden kayıt alan yarışmalar
        destekleniyor. Puanları çektikten sonra sütun başlıklarına tıklayarak sıralayabilirsin. 
        Bir hata alırsan Umut'a haber.</p>

    <div class="form-container">
        <input type="text" id="urlInput" value="https://www.efesultra.org/utrail-ephesus-120k-%e2%80%8b/" />
        <button onclick="fetchParticipants()">Yarışmacıları Getir</button>
    </div>
    <table id="participantsTable">
        <thead>
            <tr>
                <th onclick="sortTable(0)">İsim</th>
                <th onclick="sortTable(1)">Cinsiyet</th>
                <th onclick="sortTable(2)">Yaş Grubu</th>
                <th onclick="sortTable(3)">Takım</th>
                <th onclick="sortTable(4)">ITRA</th>
            </tr>
        </thead>
        <tbody id="participantsTableBody">
            <!-- Participants will be populated here -->
        </tbody>
    </table>
</body>
</html>
