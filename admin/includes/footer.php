</div>
    <!-- End of Content Wrapper -->

    <!-- Bootstrap core JavaScript-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <!-- Page level plugins -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom styles for this template -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* Admin Layout Styles */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .wrapper {
            display: flex;
            width: 100%;
            height: 100vh;
            align-items: stretch;
            overflow: hidden;
        }

        #sidebar {
            min-width: 200px;
            max-width: 200px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s;
            overflow-y: auto;
            overflow-x: hidden;
        }

        #sidebar.active {
            margin-left: -200px;
        }

        #content {
            width: calc(100% - 200px);
            height: 100vh;
            transition: all 0.3s;
            margin-left: 200px;
            padding: 8px;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: #f8f9fa;
        }

        #content.active {
            width: 100%;
            margin-left: 0;
            padding: 8px;
        }

        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            border-radius: 6px;
            margin: 3px 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar-brand {
            color: #fff !important;
            text-decoration: none;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand-icon {
            font-size: 1.8rem;
            color: #ff6b6b;
            margin-right: 10px;
        }

        .sidebar-brand-text {
            font-weight: 600;
            font-size: 1.2rem;
        }

        .sidebar-divider {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 15px 0;
        }

        .sidebar-heading {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 20px;
            margin: 20px 0 10px 0;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 0.35rem;
            margin-bottom: 0.75rem;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
            padding: 0.75rem 1rem;
        }

        .card-body {
            padding: 0.75rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
            border-color: #ff6b6b;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff5252 0%, #ff3838 100%);
            border-color: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: #f8f9fa;
            padding: 0.5rem;
        }

        .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .container-fluid {
            padding-left: 5px;
            padding-right: 5px;
        }

        .row {
            margin-left: -5px;
            margin-right: -5px;
        }

        .col, .col-md-3, .col-md-4, .col-md-6, .col-lg-3, .col-lg-4, .col-lg-6, .col-12 {
            padding-left: 5px;
            padding-right: 5px;
        }

        h1, h2, h3, h4, h5, h6 {
            margin-bottom: 0.5rem;
        }

        .d-flex {
            margin-bottom: 0.5rem;
        }

        .border-bottom {
            margin-bottom: 0.75rem !important;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -200px;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content {
                width: 100%;
                margin-left: 0;
                padding: 5px;
                height: 100vh;
            }
            
            #content.active {
                margin-left: 200px;
                padding: 5px;
                height: 100vh;
            }

            .container-fluid {
                padding-left: 3px;
                padding-right: 3px;
            }

            .row {
                margin-left: -3px;
                margin-right: -3px;
            }

            .col, .col-md-3, .col-md-4, .col-md-6, .col-lg-3, .col-lg-4, .col-lg-6, .col-12 {
                padding-left: 3px;
                padding-right: 3px;
            }
        }

        @media (max-width: 1024px) {
            #sidebar {
                min-width: 180px;
                max-width: 180px;
            }
            
            #sidebar.active {
                margin-left: -180px;
            }
            
            #content {
                width: calc(100% - 180px);
                margin-left: 180px;
                height: 100vh;
            }
            
            #content.active {
                width: 100%;
                margin-left: 0;
                height: 100vh;
            }
        }

        /* Print styles */
        @media print {
            #sidebar, .btn-toolbar {
                display: none !important;
            }
            
            #content {
                margin: 0 !important;
                width: 100% !important;
            }
            
            .card {
                border: 1px solid #000 !important;
                page-break-inside: avoid;
            }
        }
    </style>

    <script>
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const toggleBtn = document.createElement('button');
            
            // Create toggle button
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.className = 'btn btn-primary position-fixed';
            toggleBtn.style.cssText = 'top: 20px; left: 20px; z-index: 1001; display: none;';
            toggleBtn.onclick = function() {
                sidebar.classList.toggle('active');
                content.classList.toggle('active');
            };
            
            document.body.appendChild(toggleBtn);
            
            // Show toggle button on mobile
            function checkScreenSize() {
                if (window.innerWidth <= 768) {
                    toggleBtn.style.display = 'block';
                    sidebar.classList.add('active');
                    content.classList.add('active');
                } else if (window.innerWidth <= 1024) {
                    toggleBtn.style.display = 'none';
                    sidebar.classList.remove('active');
                    content.classList.remove('active');
                } else {
                    toggleBtn.style.display = 'none';
                    sidebar.classList.remove('active');
                    content.classList.remove('active');
                }
            }
            
            checkScreenSize();
            window.addEventListener('resize', checkScreenSize);
        });
    </script>
</body>
</html>
?>
