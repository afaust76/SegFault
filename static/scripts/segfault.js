$(document).ready(function() {

  $(".disabled").click(function() {
    alert("Still Developing");
    return false;
  });

  $("#toggleShowRegPasswordBtn").click(function() {
    if ($(this).attr("aria-pressed") === "false") {
        $("#passwordDiv").removeClass("col-md-6").addClass("col-md-11");
        $("#passwordDiv2").addClass("hidden");
        $("#regPassword").attr("type", "text");
        $("#toggleShowRegPasswordBtn").html("😲").removeClass("btn-secondary").addClass("btn-primary");
    } else {
        $("#passwordDiv").removeClass("col-md-11").addClass("col-md-6");
        $("#passwordDiv2").removeClass("hidden");
        $("#regPassword").attr("type", "password");
        $("#toggleShowRegPasswordBtn").html("😑").removeClass("btn-primary").addClass("btn-secondary");
    }
  });


  $("#regEmail").blur(function() {
    if ($("#regEmail").val()!=="") {
      $.ajax({ url: "/api/member/validate?email="+$("#regEmail").val(), method: "get"})
      .done(function(data) {
        var result = $.parseJSON(data);
        if (result.success=="1") {
          $("#regEmailHint").text("");
          $("#regEmail").addClass("is-valid").removeClass("is-invalid");
        } else {
          $("#regEmailHint").text(result.message);
          $("#regEmail").addClass("is-invalid").removeClass("is-valid");;
        }
      });
    } else {
      $("#regEmailHint").text("");
      $("#regEmail").removeClass("is-invalid");
      $("#regEmail").removeClass("is-valid");
    }
  });

  $("#regUsername").blur(function() {
    if ($("#regUsername").val()!=="") {
      $.ajax({ url: "/api/member/validate?username="+$("#regUsername").val(), method: "get"})
      .done(function(data) {
        var result = $.parseJSON(data);
        if (result.success=="1") {
          $("#regUsernameHint").text("");
          $("#regUsername").addClass("is-valid").removeClass("is-invalid");;
        } else {
          $("#regUsernameHint").text(result.message);
          $("#regUsername").addClass("is-invalid").removeClass("is-valid");;;
        }
      });
    } else {
      $("#regUsernameHint").text("");
      $("#regUsername").removeClass("is-invalid");
      $("#regUsername").removeClass("is-valid");
    }
  });

  $("#regForm").submit(function() {
    if ($("#regUsername").val() === "") {
      $("#regUsername").addClass("is-invalid");
      $("#regUsernameHint").text("Username cannot be blank");
      return false;
    } else {
      $("#regUsername").removeClass("is-invalid");
      $("#regUsernameHint").text("");
    }

    if ($("#regEmail").val() === "") {
      $("#regEmail").addClass("is-invalid");
      $("#regEmailHint").text("Email cannot be blank");
      return false;
    } else {
      $("#regEmail").removeClass("is-invalid");
      $("#regEmailHint").text("");
    }

    if ($("#regPassword").val() === "") {
      $("#regPassword").addClass("is-invalid");
      $("#regPasswordHint").text("Password cannot be blank");
      return false;
    } else {
      $("#regPassword").removeClass("is-invalid");
      $("#regPasswordHint").text("");
    }

    if (!$("#agreeCheckbox").prop("checked")) {
      $("#agreeCheckbox").addClass("is-invalid");
      $("#regAgreementHint").text("You must agree the terms to register");
      return false;
    } else {
      $("#agreecheckbox").removeClass("is-invalid");
      $("#regAgreementHint").text("");
    }

    if ($("#toggleShowRegPasswordBtn").attr("aria-pressed") === "false") {
      if ($("#regPassword").val() !== $("#regPassword2").val()) {
        $("#regPassword2").addClass("is-invalid");
        $("#regPassword2Hint").text("Two passwords are different!");
        return false;
      } else {
        $("#regPassword2").removeClass("is-invalid");
        $("#regPassword2Hint").text("");
      }
      $("#regPassword").val($.md5($("#regPassword").val()));
    }
  });


  $("#toggleShowLoginPasswordBtn").click(function() {
    if ($(this).attr("aria-pressed") === "false") {
        $("#loginPassword").attr("type", "text");
        $("#toggleShowLoginPasswordBtn").html("😲").removeClass("btn-secondary").addClass("btn-primary");
    } else {
        $("#loginPassword").attr("type", "password");
        $("#toggleShowLoginPasswordBtn").html("😑").removeClass("btn-primary").addClass("btn-secondary");
    }
  });

  $("#loginForm").submit(function() {
    if ($("#loginUsername").val() === "") {
      alert("Username cannot be blank");
      return false;
    }
    if ($("#loginPassword").val() === "") {
      alert("Password cannot be blank");
      return false;
    }
    $("#loginPassword").val($.md5($("#loginPassword").val()));
  });


  $(".registerBtn").click(function() {
    window.location.href = "/member/register?redirect="+redirectURI;
  });

  $(".loginBtn").click(function() {
    window.location.href = "/member/login?redirect="+redirectURI;
  });



  $(".tagBtn").click(function(){
    window.location.href = "/questions/search?tag="+$(this).attr("tagid");
  });



  $("#newThreadForm").submit(function() {
    var tags = [];
    $('#tagsList').children('span').each(function () {
      tags.push(this.getAttribute("tagid"));
    });
    if (tags.length == 0) {
      $("#newThreadTagSearchboxHint").text("You must add at least one tag to your question.");
      $("#newThreadTagSearchbox").addClass("is-invalid");
      return false;
    }
    $("#hiddenTags").val(tags.join(","));
  });

  $("#newPostForm").submit(function() {
    $("#hiddenEditedHTML").val($('#summernote').summernote('code'));
  });


  $("#newThreadTagSearchbox").change(function() {
      var tagName=$("#newThreadTagSearchbox").val();
      var obj=$("#tagList").find("option[value='"+tagName+"']");
      if (obj !== null && obj.length>0) {
        var tagid = obj.attr('tagid');
        tagName = obj.val();
        if ($("#newThreadTag-"+tagid).length == 0) {
          var $newTag = $("<span id='newThreadTag-" + tagid + "' tagid='" + tagid + "' class='badge badge-dark'>" + tagName +"<div class='removeTag' onclick='removeTag(" + tagid + ");'>&times;</div></span>");
          $("#tagsList").append($newTag);
          $("#newThreadTagSearchbox").val("");
          $("#newThreadTagSearchbox").removeClass("is-invalid");
          return true;
        }
        $("#newThreadTagSearchbox").val("");
        return false;
      }
      else {
        alert("No maching tag");
        return false;
      }
  });


  $("#loadMoreAnswers").click(function() {
    if (currentAnswers < totalAnswers) {
      var url = "/api/forum/loadpost?tid="+tid+"&offset="+Number(currentAnswers)+"&count="+Number(answersEachLoad);
      // alert(url);
      $.ajax({ url: url, method: "get"})
      .done(function(data) {
        // alert(data);
        var result = $.parseJSON(data);
        if (result.success=="1") {
          var newPosts = 0;
          result.message.forEach(function(item, index) {
            newPosts++;
            var obj = `
            <div class="question-content">
              <div>
                `+item.content+`
              </div>
              <div class="question-content-author">
                <div class="operationBar">
                  <a class="vote" onclick="vote('upvote', 0, `+item.pid+`)">
                    <div class="voteBtn badge badge-success">👍 <span id="upvote-0-`+item.pid+`">`+item.upvote+`</span></div>
                  </a>
                  <a class="vote" onclick="vote('downvote', 0, `+item.pid+`)">
                    <div class="voteBtn badge badge-danger">👎 <span id="downvote-0-`+item.pid+`">`+item.downvote+`</span></div>
                  </a>
                  <div class="modOperationBar">`;
            if (result.isModerator) {
              obj = obj+`
                    <button class="btn btn-secondary" onclick="RemovePost(`+item.pid+`)">Remove</button>
                    <button class="btn btn-secondary" onclick="edit(`+item.pid+`)">Edit</button>`;
            }   
            obj = obj+`
                  </div>
                </div>
                <div class="authorBar">
                  <div class="avatar">
                    <img class="avatar-40" src="`+item.avatar+`">
                  </div>
                  <div class="author">
                    <a href="/member/profile/`+item.uid+`">
                      `+item.username+`
                    </a><br>`+item.sendtime+`
                  </div>
                </div>
              </div>
            </div>
            <!-- text editor here -->
            <div id="editpost`+item.pid+`" style="display:none;">
               <textarea id="textedit`+item.pid+`"></textarea>
               <button class="btn btn-primary" onclick="EditPost(`+item.pid+`)">Edit</button>
               <hr />
            </div>
            `;
            $("#question-answers").append(obj);
	    initTinyMCE("textedit"+item.pid);  
          });
          currentAnswers += newPosts;
          $("#currentAnswers").text(currentAnswers);

          $('pre > code').each(function(i, block) { //Syntax highlighting
            $(this).removeClass();
            $(this).addClass($(this).parent().attr("class"));
            hljs.highlightBlock(block);
          });
          
          if (currentAnswers >= totalAnswers) {
            $("#loadMoreAnswers").text("All answers are displayed");
          }
        } else {
          alert("Load answers failed: "+result.message);
        }
      });
    }

  });


  $("#loadMoreQuestions").click(function() {
  });


  $("#loadMoreAnswers").click();

  initTinyMCE("tinyMCE");

  if (typeof schemeList != 'undefined') {
    alert(1);
    $.each(schemeList, function(index, value) {
      $('#schemeList').append( $('<option/>').attr("value", value) );
    });
  }

});


function modalalert(title, content) {
  if (title === undefined) {title = ""; content = "";}
  if (content === undefined) {content = title; title="Alert";}
  $("#modalalert-title").html(title);
  $("#modalalert-content").html(content);
  $("#modalalert").click();
  $("#modalalert").blur();
}


function removeTag(tagid) {
  var tag = document.getElementById("newThreadTag-"+tagid);
  tag.outerHTML = "";
  delete tag;
}


function vote(ud, tid, pid) {
  $.ajax({ url: "/api/forum/vote", data: { ud: ud, tid: tid, pid: pid}, method: "get"})
    .done(function(data) {
      var score = jQuery.parseJSON(data);
      $("#upvote-"+tid+"-"+pid).text(score.message.upvote);
      $("#downvote-"+tid+"-"+pid).text(score.message.downvote);
    })
    .fail(function(data) {
      alert("Vote Failed!");
    })
};

function RemovePost(pid){
    $.ajax({ url: "/api/forum/removepost", data: { pid: pid }, method: "get"})
  .done(function(data) {
      window.location.reload();
  })
};

function RemoveThread(tid){
  $.ajax({ url: "/api/forum/removethread", data: { tid: tid }, method: "get"})
  .done(function(data) {
      window.location = "/";
  })
};

function LockThread(tid){
  $.ajax({ url: "/api/forum/lockthread", data: { tid: tid }, method: "get"})
    .done(function(data) {
  window.location.reload();
    })
};

function EditPost(pid){
    var content = tinyMCE.get('textedit'+pid).getContent();
    $.ajax({ url: "/api/forum/editpost", data: { pid: pid, content: content}, method: "get"})
	.done(function(data){
	    window.location.reload();
	})
};

function edit(pid){
    document.getElementById('editpost'+pid).style.display = "block";
};

function initTinyMCE(textAreaID) {
  tinymce.init({
    selector: 'textarea#' + textAreaID,
    plugins: 'codesample',
    codesample_languages: [
      {text: 'HTML/XML', value: 'html'},
      {text: 'JavaScript', value: 'javascript'},
      {text: 'CSS', value: 'css'},
      {text: 'PHP', value: 'php'},
      {text: 'Ruby', value: 'ruby'},
      {text: 'Python', value: 'python'},
      {text: 'Java', value: 'java'},
      {text: 'C', value: 'c'},
      {text: 'C#', value: 'csharp'},
      {text: 'C++', value: 'cpp'}
    ],
    toolbar: 'undo redo | styleselect | bold italic underline strikethrough superscript subscript | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | codesample'
  });
}

function initInlineTinyMCE(divID) {
  tinymce.init({
    selector: 'div#' + textAreaID,
    inline: true;
    plugins: 'codesample',
    codesample_languages: [
      {text: 'HTML/XML', value: 'html'},
      {text: 'JavaScript', value: 'javascript'},
      {text: 'CSS', value: 'css'},
      {text: 'PHP', value: 'php'},
      {text: 'Ruby', value: 'ruby'},
      {text: 'Python', value: 'python'},
      {text: 'Java', value: 'java'},
      {text: 'C', value: 'c'},
      {text: 'C#', value: 'csharp'},
      {text: 'C++', value: 'cpp'}
    ],
    toolbar: 'undo redo | styleselect | bold italic underline strikethrough superscript subscript | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | codesample'
  });
}