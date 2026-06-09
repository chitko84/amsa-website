(function () {
    "use strict";

    function normalize(text) {
        return String(text || "")
            .toLowerCase()
            .replace(/[^a-z0-9#\s]/g, " ")
            .replace(/\s+/g, " ")
            .trim();
    }

    function escapeHtml(text) {
        return String(text || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function makeRule(patterns, answer) {
        return { patterns: patterns, answer: answer };
    }

    var publicRules = [
        makeRule(["what is amsa", "about amsa", "amsa meaning", "who is amsa", "what does amsa stand for", "aiu myanmar students association"], "AMSA AIU stands for AIU Myanmar Students' Association. It supports Myanmar students at Albukhary International University through community, culture, leadership, and service."),
        makeRule(["what does amsa do", "amsa activities", "purpose of amsa", "amsa work", "what are amsa services"], "AMSA organizes student support, community engagement, cultural activities, leadership opportunities, events, fundraising, and welfare-related initiatives."),
        makeRule(["where is amsa located", "amsa location", "where are you located", "where is aiu", "location"], "AMSA AIU is based at Albukhary International University in Malaysia."),
        makeRule(["contact amsa", "how can i contact", "contact page", "send message", "message to amsa"], "You can contact AMSA through the Contact page or email <a href=\"mailto:amsa@student.aiu.edu.my\">amsa@student.aiu.edu.my</a>."),
        makeRule(["amsa email", "email address", "what is amsa email", "where is email", "mail amsa"], "AMSA email is <a href=\"mailto:amsa@student.aiu.edu.my\">amsa@student.aiu.edu.my</a>."),
        makeRule(["how can i register", "where can i register", "register account", "create account", "sign up", "signup"], "Use the Register button in the navigation bar to create an AMSA Points member account."),
        makeRule(["how can i join", "join amsa", "become member", "join community", "membership"], "You can join by registering for an AMSA Points member account and participating in AMSA activities."),
        makeRule(["events", "where can i see events", "event page", "latest events", "news"], "Visit the Events & News page to see AMSA announcements, workshops, volunteer updates, and community activities."),
        makeRule(["achievements", "where can i see achievements", "awards", "milestones", "success"], "Visit the Achievements page to see AMSA milestones, recognition, and success stories."),
        makeRule(["community engagement", "cme", "community service", "volunteer work", "student welfare", "cultural activities"], "Community Engagement includes service projects, student welfare support, volunteer work, and cultural activities that connect AMSA with the AIU community."),
        makeRule(["fundraising", "fund raising", "donation", "student assistance", "emergency support", "fundraising initiatives"], "AMSA fundraising supports student assistance, emergency needs, welfare activities, and community initiatives."),
        makeRule(["committee", "committee members", "top management", "president", "vice president", "secretary", "treasurer"], "You can view AMSA leadership on the Top Management page, including the President, Vice President, Secretary, and Treasurer roles."),
        makeRule(["developer team", "developers", "dev team", "website contributors", "who built website"], "The Developer Team page introduces the contributors who maintain the AMSA website, points system, and digital tools."),
        makeRule(["pages", "available pages", "website pages", "site menu", "navigation"], "The website includes Home, Events & News, About AMSA, Achievements, Top Management, Community Engagement, Fundraising, Contact, Dev Team, and AMSA Points registration."),
        makeRule(["purpose of website", "why this website", "what is this website for", "website purpose"], "This website helps visitors understand AMSA AIU, follow updates, learn about programs, contact AMSA, and register for AMSA Points."),
        makeRule(["amsa points", "points system", "what is points", "member points", "activity points"], "AMSA Points is a member activity tracking system where members submit activities and earn points after approval."),
        makeRule(["submit message", "contact form", "how to submit message", "send inquiry", "ask amsa"], "Use the Contact page form to submit a message to AMSA. You can also email <a href=\"mailto:amsa@student.aiu.edu.my\">amsa@student.aiu.edu.my</a>."),
        makeRule(["about page", "learn more about", "about us"], "The About AMSA page explains the association's mission, vision, values, and student support role."),
        makeRule(["home page", "homepage", "summary"], "The homepage gives a quick overview of AMSA AIU, leadership, events, achievements, community work, fundraising, and the developer team."),
        makeRule(["register button", "where is register button", "join points"], "The Register button is in the main navigation bar and opens the AMSA Points registration page."),
        makeRule(["facebook", "instagram", "linkedin", "social media"], "AMSA social media links are available in the top bar and footer of the website."),
        makeRule(["aiu", "albukhary", "university"], "AMSA AIU serves Myanmar students at Albukhary International University."),
        makeRule(["culture", "myanmar culture", "cultural engagement"], "AMSA supports cultural engagement by helping Myanmar students share traditions, community identity, and cultural activities at AIU."),
        makeRule(["leadership", "student leaders", "management"], "AMSA encourages student leadership through committee roles, event coordination, service work, and member participation."),
        makeRule(["service", "community", "support students"], "AMSA supports students through community service, welfare initiatives, peer support, and student-led activities.")
    ];

    var pointsRules = [
        makeRule(["what is amsa points", "points system", "what is points", "member points", "dashboard"], "AMSA Points is a member activity tracking system. Members submit eligible activities, admins review them, and approved requests add points to the member account."),
        makeRule(["earn points", "how do i earn points", "get points", "gain points", "activity points"], "You earn points by submitting eligible AMSA activities with clear evidence. Points are added only after an admin approves the request."),
        makeRule(["submit activity", "submit request", "how do i submit", "activity request", "request points"], "Go to Submit Activity, choose the activity category, write a clear description, upload evidence if required, and submit the request for review."),
        makeRule(["evidence", "upload evidence", "proof", "what evidence", "participation proof"], "Upload clear evidence that proves your participation or contribution, such as a relevant image or document requested for the activity."),
        makeRule(["file types", "accepted file", "jpg", "jpeg", "png", "webp", "pdf", "upload type"], "Use accepted evidence file types shown on the submission form. Profile photos support JPG, JPEG, PNG, and WEBP."),
        makeRule(["pending", "what is pending", "pending status", "waiting review"], "Pending means your request has been submitted and is waiting for admin review. Points are not awarded yet."),
        makeRule(["approved", "what is approved", "approved status", "points approved"], "Approved means an admin accepted your request and the points were added to your total."),
        makeRule(["rejected", "what is rejected", "rejected status", "request rejected"], "Rejected means the request was not accepted. Check the remarks for the reason and submit a clearer request next time if needed."),
        makeRule(["leaderboard calculated", "how is leaderboard calculated", "ranking", "rank", "points ranking"], "The leaderboard ranks active members by approved points. Members with zero approved requests and zero points are not ranked yet."),
        makeRule(["not ranked", "why am i not ranked", "rank missing", "not on leaderboard", "zero points"], "You will appear in the leaderboard after you receive at least one approved point request. New accounts with zero approved points are not ranked yet."),
        makeRule(["member #id", "why member id", "names hidden", "anonymous", "privacy"], "Member names and emails are hidden on the public/member leaderboard for privacy. Members are shown as Member #ID. Admins can view full details for management purposes."),
        makeRule(["profile photo", "upload profile photo", "profile image", "avatar", "change photo"], "Go to My Profile to upload or change your profile photo. Use a clear JPG, JPEG, PNG, or WEBP image."),
        makeRule(["crop profile", "crop photo", "crop image", "image crop"], "After selecting a profile image, use the crop tool if shown, then save the cropped image."),
        makeRule(["change profile image", "replace profile image", "new profile photo"], "Open My Profile, choose a new image, crop it if needed, and save the profile update."),
        makeRule(["admins submit", "admin cannot submit", "why can t admins submit", "system admin submit"], "Admin accounts cannot submit point requests because only member accounts are eligible for leaderboard ranking."),
        makeRule(["who approves", "admin review", "review request", "approve points"], "AMSA admins review point requests and approve or reject them based on the submitted activity and evidence."),
        makeRule(["contact amsa", "help", "support", "email"], "You can contact AMSA at <a href=\"mailto:amsa@student.aiu.edu.my\">amsa@student.aiu.edu.my</a>."),
        makeRule(["request rejected what do i do", "what should i do if rejected", "rejected request help"], "Read the admin remarks, prepare clearer evidence or description, and submit a new request if the activity is still eligible."),
        makeRule(["approval time", "how long approval", "when approved", "review time"], "Approval time depends on admin availability. Check your dashboard for status updates."),
        makeRule(["delete request", "can i delete", "remove request"], "If deletion is available for your request, use the provided delete action. Otherwise, contact AMSA for help."),
        makeRule(["see other names", "other members names", "can i see emails", "private members"], "Normal members cannot see other members' names or emails on the leaderboard. This protects member privacy."),
        makeRule(["why names hidden", "leaderboard privacy", "anonymous leaderboard"], "Names are hidden so members can compare ranking without exposing personal details. The leaderboard uses Member #ID for privacy."),
        makeRule(["dashboard", "my dashboard", "my points page"], "Your dashboard shows total points, current rank, pending requests, approved requests, rejected requests, and request history."),
        makeRule(["status filter", "filter requests", "request history", "history"], "Use the request history filters to view all, pending, approved, or rejected submissions."),
        makeRule(["remarks", "admin remarks", "feedback"], "Admin remarks explain review decisions. They are especially useful when a request is rejected."),
        makeRule(["evidence link", "view evidence", "uploaded proof"], "Use the View evidence link in your request history to check the uploaded proof."),
        makeRule(["total points", "my total", "points total"], "Total points are the sum of points from approved requests after any corrections or reversals."),
        makeRule(["categories", "activity category", "point category"], "Activity categories define what type of contribution you are submitting and how many points may be awarded."),
        makeRule(["submit twice", "duplicate", "double points"], "Avoid duplicate submissions for the same activity unless instructed. Admins review requests to prevent incorrect points."),
        makeRule(["logout", "sign out"], "Use the profile menu or navigation logout option to securely sign out of AMSA Points."),
        makeRule(["register", "create account", "new account"], "Use the AMSA Points registration page to create a member account before submitting activities."),
        makeRule(["login", "sign in"], "Use the AMSA Points login page with your registered email and password."),
        makeRule(["inactive", "suspended", "cannot login"], "Inactive or suspended accounts may be unable to use the system. Contact AMSA for help."),
        makeRule(["safe evidence", "private evidence", "personal information"], "Upload only relevant evidence. Avoid unnecessary private information in files or descriptions."),
        makeRule(["mobile", "phone", "responsive"], "The AMSA Points pages work on mobile. Use the menu to navigate to Dashboard, Submit Activity, Leaderboard, and Profile."),
        makeRule(["profile", "my profile"], "My Profile lets you update your account details and profile image."),
        makeRule(["leaderboard", "view leaderboard"], "The Leaderboard shows ranked active members who have approved points, using Member #ID for privacy."),
        makeRule(["submit button", "where submit"], "Use Submit Activity from the points navigation or dashboard action button."),
        makeRule(["points not added", "approved but no points", "missing points"], "Points are added after approval. If something looks wrong, contact AMSA with your request details."),
        makeRule(["old request", "previous request", "past submission"], "Your request history keeps previous submissions with status, evidence, remarks, and dates."),
        makeRule(["reviewed date", "review date"], "Reviewed Date shows when an admin approved or rejected your request."),
        makeRule(["requested date", "submission date"], "Requested Date shows when you submitted the activity request."),
        makeRule(["description", "activity description"], "Write a concise description explaining what you did and why it qualifies for the selected category."),
        makeRule(["clear proof", "good evidence"], "Good evidence should clearly show your participation, contribution, event, date, or relevant confirmation."),
        makeRule(["admin account", "executive account"], "Admin and executive accounts manage or review the system but should use a member account for personal activity submissions."),
        makeRule(["member account", "normal member"], "Member accounts can submit activities and appear on the leaderboard after approved points."),
        makeRule(["rank #1", "zero rank"], "Zero-point members are not ranked. A rank appears only after approved activity or more than zero total points.")
    ];

    var presets = {
        public: {
            name: "AMSA Assistant",
            subtitle: "Website navigation help",
            greeting: "Hi, I am AMSA Assistant. Choose a question below.",
            fallback: "I am not fully sure about that. You can explore the website menu or contact AMSA at <a href=\"mailto:amsa@student.aiu.edu.my\">amsa@student.aiu.edu.my</a> for more help.",
            quickQuestions: ["What is AMSA?", "How can I contact AMSA?", "Where can I register?", "Show me events"],
            rules: publicRules
        },
        points: {
            name: "AMSA Points Assistant",
            subtitle: "Points system help",
            greeting: "Hi, I am AMSA Points Assistant. Choose a question below.",
            fallback: "I am not fully sure about that. You can check the Dashboard, Submit Activity, Leaderboard, or contact AMSA at <a href=\"mailto:amsa@student.aiu.edu.my\">amsa@student.aiu.edu.my</a>.",
            quickQuestions: ["How do I earn points?", "Why am I not ranked?", "What evidence can I upload?", "Why are names hidden?"],
            rules: pointsRules
        }
    };

    function findAnswer(config, question) {
        var normalized = normalize(question);
        for (var i = 0; i < config.rules.length; i += 1) {
            var patterns = config.rules[i].patterns;
            for (var j = 0; j < patterns.length; j += 1) {
                if (normalized.indexOf(normalize(patterns[j])) !== -1) {
                    return config.rules[i].answer;
                }
            }
        }
        return config.fallback;
    }

    function addMessage(messages, type, html) {
        var message = document.createElement("div");
        message.className = "amsa-chatbot-message " + type;
        message.innerHTML = type === "user" ? escapeHtml(html) : html;
        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
    }

    function initChatbot(options) {
        var config = presets[options.preset || "public"];
        if (!config || document.querySelector(".amsa-chatbot")) {
            return;
        }

        var root = document.createElement("div");
        root.className = "amsa-chatbot";
        root.innerHTML = '' +
            '<button class="amsa-chatbot-toggle" type="button" aria-label="Open ' + escapeHtml(config.name) + '"><i class="fas fa-robot" aria-hidden="true"></i></button>' +
            '<section class="amsa-chatbot-panel" aria-label="' + escapeHtml(config.name) + '">' +
            '<div class="amsa-chatbot-header"><div><h2 class="amsa-chatbot-title">' + escapeHtml(config.name) + '</h2><p class="amsa-chatbot-subtitle">' + escapeHtml(config.subtitle) + '</p></div><button class="amsa-chatbot-close" type="button" aria-label="Close chat">×</button></div>' +
            '<div class="amsa-chatbot-messages"></div>' +
            '<div class="amsa-chatbot-quick" aria-label="Frequently asked questions"></div>' +
            '</section>';
        document.body.appendChild(root);

        var toggle = root.querySelector(".amsa-chatbot-toggle");
        var close = root.querySelector(".amsa-chatbot-close");
        var messages = root.querySelector(".amsa-chatbot-messages");
        var quick = root.querySelector(".amsa-chatbot-quick");

        addMessage(messages, "bot", config.greeting);

        config.quickQuestions.forEach(function (question) {
            var chip = document.createElement("button");
            chip.className = "amsa-chatbot-chip";
            chip.type = "button";
            chip.textContent = question;
            chip.addEventListener("click", function () {
                var value = String(question || "").trim();
                if (!value) {
                    return;
                }
                addMessage(messages, "user", value);
                addMessage(messages, "bot", findAnswer(config, value));
            });
            quick.appendChild(chip);
        });

        toggle.addEventListener("click", function () {
            root.classList.toggle("open");
        });

        close.addEventListener("click", function () {
            root.classList.remove("open");
        });
    }

    window.AmsaChatbot = {
        init: initChatbot,
        presets: presets
    };
})();
