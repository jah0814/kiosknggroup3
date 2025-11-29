<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DLSP Registrar Kiosk</title>
    <link rel="icon" type="image/jpeg" href="favicon.php" />
    <link rel="shortcut icon" type="image/jpeg" href="favicon.php" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        body.kiosk {
            background: url('assets/images/university-entrance.png');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
        }
        /* Ensure all input fields in index.php have the correct background */
        #kioskForm input[type="text"],
        #kioskForm input[type="password"],
        #kioskForm select {
            background: rgba(13, 176, 122, 0.14) !important;
            border: 1px solid #0A3C2E !important;
            color: #0f1f1a;
        }
        #kioskForm input::placeholder {
            color: rgba(15, 31, 26, 0.6);
        }
    </style>
</head>
<body class="kiosk">
    <div class="container">
        <header>
            <div class="brand">
                <img src="assets/images/441281302_977585947490493_7271137553168216114_n.jpg" alt="DLSP Logo" />
                <div class="title">
                    <h1>Registrar Kiosk</h1>
                    <p>Dalubhasaan ng Lungsod ng San Pablo</p>
                </div>
            </div>
        </header>

        <form id="kioskForm" class="card">
            <div class="stepper">
                <div class="step active" data-step="1">1</div>
                <div class="step-line"></div>
                <div class="step" data-step="2">2</div>
                <div class="step-line"></div>
                <div class="step" data-step="3">3</div>
            </div>

            <div id="step1">
                <div class="step-field">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" placeholder="e.g. 2023-000123" required />
                </div>
                <div class="step-field">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required />
                </div>
                <div class="step-field">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <option value="BSEd">BSEd</option>
                        <option value="BTVTed">BTVTed</option>
                        <option value="BSNed">BSNed</option>
                        <option value="BEEd">BEEd</option>
                        <option value="BPed">BPed</option>
                        <option value="TCP">TCP</option>
                        <option value="BSIT">BSIT</option>
                        <option value="BSIS">BSIS</option>
                        <option value="MBA">MBA</option>
                        <option value="MPA">MPA</option>
                        <option value="MAED">MAED</option>
                        <option value="BSBA">BSBA</option>
                        <option value="BSAIS">BSAIS</option>
                        <option value="BSMA">BSMA</option>
                        <option value="BS HOSPITALITY MANAGEMENT">BS HOSPITALITY MANAGEMENT</option>
                        <option value="BSOA">BSOA</option>
                        <option value="BSENTREP">BSENTREP</option>
                        <option value="BSCPE">BSCPE</option>
                        <option value="AET">AET</option>
                        <option value="BSPSY">BSPSY</option>
                        <option value="BSECO">BSECO</option>
                        <option value="BSCOMM">BSCOMM</option>
                        <option value="BSPA">BSPA</option>
                        <option value="BSA">BSA</option>
                        <option value="ABPOLSCI">ABPOLSCI</option>
                    </select>
                </div>
                <div class="step-footer">
                    <button type="button" class="enter-btn" id="toStep2">ENTER</button>
                </div>
            </div>

            <div id="step2" class="hidden">
                <div class="form-row">
                    <h2 class="section-title">Purpose of Visit</h2>
                    <div class="purpose-grid" id="purposeGrid">
                        <div class="purpose-tile" data-value="Document Request">Document Request</div>
                        <div class="purpose-tile" data-value="Enrollment Stamp">Enrollment Stamp</div>
                        <div class="purpose-tile" data-value="Grade Slip">Grade Slip</div>
                        <div class="purpose-tile" data-value="Diploma/Transcript">Diploma/Transcript</div>
                        <div class="purpose-tile others" data-value="Others">Others</div>
                    </div>
                    <div class="form-row hidden" id="otherPurposeRow">
                        <label for="otherPurpose">Please specify</label>
                        <input type="text" id="otherPurpose" placeholder="Describe your request" />
                    </div>
                    <input type="hidden" id="purpose" name="purpose" required />
                </div>
                <div class="wizard-actions">
                    <button type="button" class="btn back-btn" id="backTo1">Back</button>
                    <span class="spacer"></span>
                    <button type="button" class="btn next-btn" id="toStep3">Next</button>
                </div>
            </div>

            <div id="step3" class="hidden">
                <div class="form-row">
                    <label>Confirm Details</label>
                    <div class="card" style="margin:8px 0;">
                        <div><strong>ID:</strong> <span id="previewId">&mdash;</span></div>
                        <div><strong>Name:</strong> <span id="previewName">&mdash;</span></div>
                        <div><strong>Department:</strong> <span id="previewDepartment">&mdash;</span></div>
                        <div><strong>Purpose:</strong> <span id="previewPurpose">&mdash;</span></div>
                    </div>
                </div>
                <div class="wizard-actions">
                    <button type="button" class="btn step3-btn" id="backTo2">Back</button>
                    <span class="spacer"></span>
                    <button type="submit" class="btn primary step3-btn">Proceed</button>
                </div>
            </div>
        </form>

        <div id="ticket" class="card ticket hidden">
            <h2>Your Queue Number</h2>
            <div class="queue-number" id="queueNumber">REG-000</div>
            <div class="eta">Estimated wait: <span id="eta">0</span> minutes</div>
            <div class="actions">
                <button id="printBtn" class="btn">Print Ticket</button>
                <a href="display.php" target="_blank" class="btn ghost">View Display</a>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
    const steps = Array.from(document.querySelectorAll('.step'));
    const screens = {
        1: document.getElementById('step1'),
        2: document.getElementById('step2'),
        3: document.getElementById('step3'),
    };

    const studentIdInput = document.getElementById('student_id');
    const nameInput = document.getElementById('name');
    const departmentInput = document.getElementById('department');
    const purposeGrid = document.getElementById('purposeGrid');
    const purposeField = document.getElementById('purpose');
    const otherPurposeRow = document.getElementById('otherPurposeRow');
    const otherPurposeInput = document.getElementById('otherPurpose');

    const previewId = document.getElementById('previewId');
    const previewName = document.getElementById('previewName');
    const previewDepartment = document.getElementById('previewDepartment');
    const previewPurpose = document.getElementById('previewPurpose');

    const ticketPanel = document.getElementById('ticket');
    const queueNumberEl = document.getElementById('queueNumber');
    const etaEl = document.getElementById('eta');

    function setStep(step){
        Object.entries(screens).forEach(([key, panel]) => {
            panel.classList.toggle('hidden', Number(key) !== step);
        });
        steps.forEach(s => {
            s.classList.toggle('active', Number(s.dataset.step) === step);
        });
    }

    function validateStep1(){
        if (!studentIdInput.value.trim()){
            alert('Please enter your Student ID.');
            studentIdInput.focus();
            return false;
        }
        if (!nameInput.value.trim()){
            alert('Please enter your Name.');
            nameInput.focus();
            return false;
        }
        if (!departmentInput.value.trim()){
            alert('Please enter your Department.');
            departmentInput.focus();
            return false;
        }
        return true;
    }

    function validatePurpose(){
        const value = purposeField.value.trim();
        if (!value){
            alert('Please select your purpose of visit.');
            return false;
        }
        return true;
    }

    function getWindowForDepartment(dept){
        const d = (dept || '').toString().trim().toUpperCase();
        // Window 1
        if (["BSED","BTVTED","BSNED","BEED","BPED","TCP"].includes(d)) return 1;
        // Window 2
        if (["BSIT","BSIS","MBA","MPA","MAED"].includes(d)) return 2;
        // Window 3
        if (["BSBA","BSAIS","BSMA"].includes(d)) return 3;
        // Window 4
        if (["BS HOSPITALITY MANAGEMENT","BSHM","HOSPITALITY MANAGEMENT"].includes(d)) return 4;
        // Window 5
        if (["BSOA","BSENTREP","BSCPE","AET"].includes(d)) return 5;
        // Window 6
        if (["BSPSY","BSECO","BSCOMM","BSPA","BSA","ABPOLSCI"].includes(d)) return 6;
        return 1;
    }

    function updatePreview(){
        const deptValue = departmentInput.value.trim();
        const windowNum = getWindowForDepartment(deptValue);
        previewId.textContent = studentIdInput.value.trim() || '&#8212;';
        previewName.textContent = nameInput.value.trim() || '&#8212;';
        previewDepartment.textContent = deptValue
            ? `${deptValue} - Window ${windowNum}`
            : '&#8212;';
        previewPurpose.textContent = purposeField.value.trim() || '&#8212;';
    }

    document.getElementById('toStep2').addEventListener('click', () => {
        if (validateStep1()){
            setStep(2);
        }
    });
    document.getElementById('backTo1').addEventListener('click', () => setStep(1));
    document.getElementById('backTo2').addEventListener('click', () => setStep(2));

    document.getElementById('toStep3').addEventListener('click', () => {
        if (!validatePurpose()){ return; }
        updatePreview();
        setStep(3);
    });

    purposeGrid.addEventListener('click', (event) => {
        const tile = event.target.closest('.purpose-tile');
        if (!tile){ return; }
        purposeGrid.querySelectorAll('.purpose-tile').forEach(t => t.classList.remove('active'));
        tile.classList.add('active');
        const value = tile.dataset.value || '';
        if (value === 'Others'){
            otherPurposeRow.classList.remove('hidden');
            otherPurposeInput.focus();
            purposeField.value = otherPurposeInput.value.trim();
        } else {
            otherPurposeRow.classList.add('hidden');
            otherPurposeInput.value = '';
            purposeField.value = value;
        }
    });

    otherPurposeInput.addEventListener('input', () => {
        if (!otherPurposeRow.classList.contains('hidden')){
            purposeField.value = otherPurposeInput.value.trim();
        }
    });

    document.getElementById('kioskForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!validateStep1()){
            setStep(1);
            return;
        }
        if (!validatePurpose()){
            setStep(2);
            return;
        }
        const data = new FormData(e.target);
        try {
            const res = await fetch('includes/queue.php?action=create', {
                method: 'POST',
                body: data
            });
            const json = await res.json();
            if (json.ok) {
                const q = encodeURIComponent(json.queue_number);
                const e = encodeURIComponent(json.estimated_wait_minutes);
                window.location.href = `ticket.php?queue=${q}&eta=${e}`;
            } else {
                alert(json.error || 'Failed to create ticket');
            }
        } catch (error) {
            console.error(error);
            alert('Unable to submit request right now.');
        }
    });

    document.getElementById('printBtn').addEventListener('click', () => window.print());

    setStep(1);
    </script>
</body>
</html>
