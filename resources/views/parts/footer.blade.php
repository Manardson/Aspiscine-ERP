    <!-- footer start-->
    <footer class="footer">
        <div class="container-fluid" >
            <div class="row">
                <div class="col-md-6 footer-copyright">
                    <p class="mb-0">Copyright {{date('Y')}} © Aspicine</p>
                </div>
                <div class="col-md-6">
                    <p class="pull-right mb-0">Hand crafted & made with <i class="fa fa-heart font-secondary"></i> by ProjectMedia</p>
                </div>
            </div>
        </div>
    </footer>
</div>
</div>
@include('parts.modal')
@include('parts.fconscript')

@stack('js')
<script>
    function mesaj(response) {
        
              $('#snackbar').text("");
   
               $('#snackbar').text(response);
                 $("#snackbar").addClass('show');
   
               setTimeout(function() {
   
                  $('#snackbar').removeClass('show');
   
               }, 5000);
           }
   </script>
</body>

</html>
