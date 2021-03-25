(function(){
    $(document).ready(function(){
        /**
         * if user click on the vote then add vote
         */
        $(document).on('submit', '#proposal-submit', function(e){
            e.preventDefault();
            let event_id = $(this).data('event_id');
            let proposal_title = $('#proposal-submit input[name="proposal_title"]').val();
            let proposal_category= $('#proposal-submit select[name="proposal-category"]').val();
            let proposal_description= $('#proposal-submit textarea[name="proposal-description"]').val();
            let action = 'imit_proposal_submit';
            let nonce = imitProposalData.imit_porposal_nonce;
            $.ajax({
                url: imitProposalData.ajax_url,
                method: 'POST',
                data: {event_id:event_id, proposal_title:proposal_title, proposal_category:proposal_category, proposal_description:proposal_description, action:action, nonce:nonce},
                success: function(data){
                    $('#proposal-add-message').html(data);
                    $('#proposal-submit input[name="proposal_title"]').val('');
                    $('#proposal-submit textarea[name="proposal-description"]').val('');
                }
            });
        });

        /**
         * if user click create vote
         */
        let proposal_id;
        $(document).on('click', '#create-vote', function(e){
            e.preventDefault();
            proposal_id = $(this).data('proposal_id');
            proposalById(proposal_id);
        });

        /**
         * get proposal by id
         */
        function proposalById(proposal_id){
            $.ajax({
                url: imitSingleProposal.ajax_url,
                method: 'POST',
                data: {action: 'single_proposal_show', nonce:imitSingleProposal.imit_single_proposal_nonce, proposal_id:proposal_id},
                dataType: 'JSON',
                success: function(data){
                    $('#submit-vote #proposal-title').text(data[0].proposal_title);
                }
            });
        }

        /**
         * change published status
         */
        $(document).on('change', '#submit-vote input[name="vote-privacy"]', function(e){
            e.preventDefault();
           let status = $('#submit-vote input[name="vote-privacy"]:checked').val();
           if(status == 'private'){
               $('#proposal-privacy-status').addClass('bg-danger');
               $('#proposal-privacy-status').removeClass('bg-success');
               $('#proposal-privacy-status').text('Private');
           }else{
               $('#proposal-privacy-status').removeClass('bg-danger');
               $('#proposal-privacy-status').addClass('bg-success');
               $('#proposal-privacy-status').text('Published');
           }
        });

        /**
         * create a vote for logged in user
         */
        $(document).on('submit', '#submit-vote', function(e){
            e.preventDefault();
            let privacy = $('#submit-vote input[name="vote-privacy"]:checked').val();
            let status;
            if(privacy == 'private'){
                status = '2';
            }else{
                status = '1';
            }
            $.ajax({
                url: imitVoteData.ajax_url,
                method: 'POST',
                data: {action:'imit_create_vote', nonce:imitVoteData.imit_vote_nonce, proposal_id:proposal_id, status:status},
                success: function(data){
                    $('#submit-vote #imit-vote-message').html(data);
                    $('.vote-button'+proposal_id).html('<i class="fas fa-thumbs-up me-2"></i> Voated');
                    $('.vote-button'+proposal_id).addClass('disabled');
                    $('.vote-button'+proposal_id).removeAttr('data-proposal_id');
                    setTimeout(function(){
                        $('#imit-vote-message').html('');
                    }, 2000);
                }
            });
        });

        /**
         * ajax search
         */
        $(document).on('keyup change', '#proposal-ajax-searching', function(e){
            e.preventDefault();
            let tag = $('#proposal-ajax-searching input[name="tag"]').val();
            let sort = $('#proposal-ajax-searching select[name="sort"]').val();
            let category = $('#proposal-ajax-searching select[name="category"]').val();
            let event_id = $(this).data('event_id');
            $.ajax({
                url: imitAjaxSearch.ajax_url,
                method: 'POST',
                data: {action:'imit_ajax_search', nonce:imitAjaxSearch.imit_ajax_search_nonce, tag:tag, sort:sort, category:category, event_id:event_id},
                success: function(data){
                    $('#fetch-proposals').html(data);
                }
            });
        });

        /**
         * add proposal comment
         */
        $(document).on('submit', '#add_comment_on_proposal', function(e){
            e.preventDefault();
            let proposal_id = $(this).data('proposal_id');
            let proposal_comment = $('#add_comment_on_proposal textarea[name="proposal-comment"]').val();
            
            $.ajax({
                url: imitCommentAdd.ajax_url,
                method: 'POST',
                data: {proposal_id:proposal_id, proposal_comment:proposal_comment, nonce:imitCommentAdd.imit_comment_create_nonce, action:'imit_create_proposal_comment'},
                success: function(data){
                    $('#comment-message').html(data);
                    $('#add_comment_on_proposal textarea[name="proposal-comment"]').val('')
                }
            });

        });

        /**
         * create wordpress user
         */
        $(document).on('submit', '#wordpress_create_user', function(e){
            e.preventDefault();
            let name = $('#wordpress_create_user input[name="name"]').val();
            let email = $('#wordpress_create_user input[name="email"]').val();
            let password = $('#wordpress_create_user input[name="password"]').val();
            let re_pass = $('#wordpress_create_user input[name="re-password"]').val();
            
            $.ajax({
                url: imitCreateUser.ajax_url,
                method: 'POST',
                data: {name:name, email:email, password:password, re_pass:re_pass, action:'imit_create_user', nonce:imitCreateUser.imit_create_wordpress_user_nonce},
                success:function(data){
                    $('#wp_create_user_message').html(data);
                }
            });
        });

        /**
         * login custom user
         */
        $(document).on('submit', '#imit_login', function(e){
            e.preventDefault();
            let name = $('#imit_login input[name="name"]').val();
            let password = $('#imit_login input[name="password"]').val();
            
            $.ajax({
                url: imitLogin.ajax_url,
                method: 'POST',
                data: {nonce:imitLogin.imit_login_nonce, action: 'imit_custom_login', name:name, password:password},
                dataType: 'JSON',
                success:function(data){
                    $('#imit-login-error').html(data.response);
                    if(data.redirect == 'yes'){
                        window.location.href = imitLogin.redirect_to
                    }
                }
            });
        });

        /**
         * share on facebook
         */
         $('.fbsharelink').click( function() 
         {
             var shareurl = $(this).data('shareurl');
             window.open('https://www.facebook.com/sharer/sharer.php?u='+escape(shareurl)+'&t='+document.title, '', 
             'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');
             return false;
         });

         /**
          * like comment
          */
         $(document).on('click', '#like-proposal-comment', function(e){
             e.preventDefault();
             let comment_id = $(this).data('comment_id');
             let nonce = imitCreateLike.imit_proposal_like_create_nonce;
             let action = 'imit_like_create';
             let button = $(this);
             let counter = $('#like-counter'+comment_id).text();
             let like_action = 'like';
             $.ajax({
                 url: imitCreateLike.ajax_url,
                 type: 'POST',
                 data: {action:action, nonce:nonce, comment_id:comment_id, like_action:like_action},
                 success: function(data){
                    counter++;
                     button.removeClass('text-secondary');
                     button.addClass('primary-color');
                     button.attr('id', 'dislike-proposal-comment');
                     $('#dislike-proposal-comment #like-text').text('Liked');
                     $('#like-counter'+comment_id).text(counter);
                 }
             });
         });

         /**
          * if user click dislike
          */
          $(document).on('click', '#dislike-proposal-comment', function(e){
            e.preventDefault();
            let comment_id = $(this).data('comment_id');
            let nonce = imitCreateLike.imit_proposal_like_create_nonce;
            let action = 'imit_like_create';
            let button = $(this);
            let counter = $('#like-counter'+comment_id).text();
            let like_action = 'dislike';
            $.ajax({
                url: imitCreateLike.ajax_url,
                type: 'POST',
                data: {action:action, nonce:nonce, comment_id:comment_id, like_action:like_action},
                success: function(data){
                   counter--;
                   button.removeClass('primary-color');
                    button.addClass('text-secondary');
                    button.attr('id', 'like-proposal-comment');
                    $('#like-proposal-comment #like-text').text('Like');
                    $('#like-counter'+comment_id).text(counter);
                }
            });
        });

        /**
         * show replay form
         */
        $(document).on('click', '#replay-comment', function(e){
            e.preventDefault();
            let comment_id = $(this).data('comment_id');
            $('.comment-replay').slideUp('fast');
            $('.replay'+comment_id).slideDown('fast');
        });

        /**
         * submit replay form
         */
        $(document).on('submit', '#replay-comment-form', function(e){
            e.preventDefault();
            let comment_id = $(this).data('comment_id');
            let replay_text = $('#replay-comment-form textarea[name="replay-text'+comment_id+'"]').val();
            $.ajax({
                url: imitCreateReplay.ajax_url,
                method: 'POST',
                data:{action:'imit_create_replay', nonce:imitCreateReplay.imit_proposal_comment_replay_nonce, comment_id:comment_id, replay_text:replay_text},
                success: function(data){
                    $('#replay-message'+comment_id).html(data);
                    $('#replay-comment-form textarea[name="replay-text'+comment_id+'"]').val('')
                }
            });
        });

        /**
         * tab js
         */
        $(document).on('click', '#imit-custom-tab', function(e){
            e.preventDefault();
            let imit_tab_target = $(this).data('target');
            $('.event-info').hide();
            $('.imit-custom-tab-link').removeClass('active');
            $(this).addClass('active');
            $('.event-info').hide();
            $('#'+imit_tab_target).fadeIn('fast');
        });
      
    });
})(jQuery)