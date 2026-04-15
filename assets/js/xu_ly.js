function openMembersModal() {
	document.getElementById('membersModal').style.display = 'block';
}

function closeMembersModal() {
	document.getElementById('membersModal').style.display = 'none';
}

function openReportModal() {
	document.getElementById('reportModal').style.display = 'block';
}

function closeReportModal() {
	document.getElementById('reportModal').style.display = 'none';
	document.getElementById('reportForm').reset();
}

function showFeedback(feedback) {
	// Tao modal dong de hien thi feedback
	const modal = document.createElement('div');
	modal.className = 'feedback-modal-overlay';

	const content = document.createElement('div');
	content.className = 'feedback-modal-content';

	content.innerHTML = `
		<div class="feedback-modal-header">
			<h3 class="feedback-modal-title"><i class="fas fa-comment-dots"></i> Nhận xét từ AI</h3>
			<span onclick="this.closest('.feedback-modal-overlay').remove()" class="feedback-modal-close">&times;</span>
		</div>
		<div class="feedback-modal-body">
			<div class="feedback-content-box">
				${feedback.replace(/\n/g, '<br>')}
			</div>
		</div>
	`;

	modal.appendChild(content);
	document.body.appendChild(modal);

	// Dong khi click ben ngoai
	modal.addEventListener('click', function (e) {
		if (e.target === modal) {
			modal.remove();
		}
	});
}

function submitReport(event) {
	event.preventDefault();

	const reason = document.getElementById('reportReason').value.trim();
	if (!reason) {
		alert('Vui lòng nhập lý do báo cáo!');
		return;
	}

	const configEl = document.getElementById('manageClassConfig');
	const classId = configEl ? configEl.dataset.classId : '';

	// Gui bao cao qua AJAX
	fetch('/E_learning/modules/report/report_process.php', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: `class_id=${encodeURIComponent(classId)}&report_class=true&reason=${encodeURIComponent(reason)}`
	})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert('✅ ' + data.message);
				closeReportModal();
			} else {
				alert('❌ ' + data.message);
			}
		})
		.catch(error => {
			console.error('Error:', error);
			alert('❌ Đã xảy ra lỗi khi gửi báo cáo. Vui lòng thử lại!');
		});
}

// Dong modal khi click ben ngoai - manage_class.php
window.addEventListener('click', function (event) {
	const membersModal = document.getElementById('membersModal');
	const reportModal = document.getElementById('reportModal');

	if (event.target == membersModal) {
		membersModal.style.display = 'none';
	}
	if (event.target == reportModal) {
		reportModal.style.display = 'none';
	}
});

// ===== admin_dashboard.php =====
document.addEventListener('DOMContentLoaded', function () {
	const openClassButtons = document.querySelectorAll('.js-admin-open-class');
	if (!openClassButtons.length) {
		return;
	}

	openClassButtons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			const classId = btn.dataset.classId;
			if (!classId) {
				return;
			}
			window.location.href = '/E_learning/modules/class/manage_class.php?id=' + encodeURIComponent(classId);
		});
	});
});

// ===== change_password.php =====
document.addEventListener('DOMContentLoaded', function () {
	const form = document.getElementById('changePasswordForm');
	if (!form) {
		return;
	}

	const currentPasswordInput = document.getElementById('current_password');
	if (currentPasswordInput) {
		currentPasswordInput.focus();
	}

	form.addEventListener('submit', function (event) {
		const newPassword = document.getElementById('new_password').value;
		const confirmPassword = document.getElementById('confirm_password').value;
		const currentPassword = document.getElementById('current_password').value;

		if (newPassword.length < 6) {
			alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
			event.preventDefault();
			return;
		}

		if (newPassword !== confirmPassword) {
			alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
			event.preventDefault();
			return;
		}

		if (newPassword === currentPassword) {
			alert('Mật khẩu mới không được trùng với mật khẩu hiện tại!');
			event.preventDefault();
		}
	});
});

// ===== view_reports.php =====
document.addEventListener('DOMContentLoaded', function () {
	const confirmLinks = document.querySelectorAll('.js-confirm-action');
	if (!confirmLinks.length) {
		return;
	}

	confirmLinks.forEach(function (link) {
		link.addEventListener('click', function (event) {
			const message = link.dataset.confirmMessage || 'Bạn có chắc chắn muốn tiếp tục?';
			if (!window.confirm(message)) {
				event.preventDefault();
			}
		});
	});
});

// ===== login.php =====
document.addEventListener('DOMContentLoaded', function () {
	const usernameInput = document.getElementById('username');
	if (usernameInput && document.body.classList.contains('login-page')) {
		usernameInput.focus();
	}
});

// ===== manage_lectures.php =====
document.addEventListener('DOMContentLoaded', function () {
	const deleteButtons = document.querySelectorAll('.js-confirm-delete-lecture');
	if (!deleteButtons.length) {
		return;
	}

	const configEl = document.getElementById('manageLecturesConfig');
	const classId = configEl ? configEl.dataset.classId : '';

	deleteButtons.forEach(function (button) {
		button.addEventListener('click', function () {
			const lectureId = button.dataset.lectureId;
			if (!lectureId || !classId) {
				return;
			}

			if (window.confirm('Bạn có chắc chắn muốn xóa bài giảng này?\nHành động này không thể hoàn tác!')) {
				window.location.href = '/E_learning/modules/lecture/manage_lectures.php?id=' + encodeURIComponent(classId) + '&delete_id=' + encodeURIComponent(lectureId);
			}
		});
	});
});

// ===== upload_lecture.php =====
document.addEventListener('DOMContentLoaded', function () {
	if (!document.body.classList.contains('upload-lecture-page')) {
		return;
	}

	const fileUploadArea = document.getElementById('fileUploadArea');
	const lectureFile = document.getElementById('lectureFile');
	const fileInfo = document.getElementById('fileInfo');
	const fileName = document.getElementById('fileName');
	const fileSize = document.getElementById('fileSize');

	if (!fileUploadArea || !lectureFile || !fileInfo || !fileName || !fileSize) {
		return;
	}

	fileUploadArea.addEventListener('click', function () {
		lectureFile.click();
	});

	lectureFile.addEventListener('change', function (event) {
		handleUploadFiles(event.target.files, fileName, fileSize, fileInfo);
	});

	fileUploadArea.addEventListener('dragover', function (event) {
		event.preventDefault();
		fileUploadArea.classList.add('dragover');
	});

	fileUploadArea.addEventListener('dragleave', function () {
		fileUploadArea.classList.remove('dragover');
	});

	fileUploadArea.addEventListener('drop', function (event) {
		event.preventDefault();
		fileUploadArea.classList.remove('dragover');
		const files = event.dataTransfer.files;
		lectureFile.files = files;
		handleUploadFiles(files, fileName, fileSize, fileInfo);
	});
});

function handleUploadFiles(files, fileNameElement, fileSizeElement, fileInfoElement) {
	if (!files || files.length === 0) {
		return;
	}

	const file = files[0];
	fileNameElement.textContent = file.name;
	fileSizeElement.textContent = formatUploadFileSize(file.size);
	fileInfoElement.classList.add('show');
}

function formatUploadFileSize(bytes) {
	if (bytes === 0) {
		return '0 Bytes';
	}

	const k = 1024;
	const sizes = ['Bytes', 'KB', 'MB', 'GB'];
	const i = Math.floor(Math.log(bytes) / Math.log(k));
	return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

// ===== user_dashboard.php =====
document.addEventListener('DOMContentLoaded', function () {
	if (!document.body.classList.contains('user-dashboard-page')) return;

	document.querySelectorAll('.js-goto-create-class').forEach(function (el) {
		el.addEventListener('click', function () {
			window.location.href = '/E_learning/modules/class/create_class.html';
		});
	});

	document.querySelectorAll('.js-goto-join-class').forEach(function (el) {
		el.addEventListener('click', function () {
			window.location.href = '/E_learning/modules/class/join_class.html';
		});
	});

	document.querySelectorAll('.js-enter-class').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var href = btn.dataset.href;
			if (href) window.location.href = href;
		});
	});
});

// ===== take_quiz.php =====
document.addEventListener('DOMContentLoaded', function () {
	if (!document.body.classList.contains('take-quiz-page')) return;

	var quizForm = document.getElementById('quizForm');
	if (!quizForm) return;

	var violationCount = 0;
	var tabSwitches = [];
	var MAX_VIOLATIONS = 3;
	var isWarningShown = false;
	var isAntiCheatActive = false;
	var isSubmitting = false;
	var isInFullscreen = false;

	var timerBox = document.getElementById('timerBox');
	var totalSeconds = 0;
	if (timerBox) {
		totalSeconds = parseInt(timerBox.dataset.duration || '0', 10) * 60;
	}
	var timerDisplay = document.getElementById('timer');

	function logViolation(type) {
		tabSwitches.push({ time: new Date().toISOString(), type: type });
		document.getElementById('tabSwitchesInput').value = JSON.stringify(tabSwitches);
	}

	function showWarning(type) {
		if (isWarningShown) return;
		isWarningShown = true;
		violationCount++;
		document.getElementById('violationCount').textContent = violationCount;
		document.getElementById('violationInput').value = violationCount;
		logViolation(type);

		var overlay = document.getElementById('overlay');
		var warning = document.getElementById('cheatWarning');
		var warningType = document.getElementById('warningType');
		var warningMsg = document.getElementById('warningMessage');

		if (type === 'exit_fullscreen') {
			warningType.innerHTML = 'Hệ thống phát hiện bạn đã <strong>THOÁT TOÀN MÀN HÌNH</strong>!';
		} else {
			warningType.innerHTML = 'Hệ thống phát hiện bạn đã <strong>CHUYỂN TAB/CỬA SỔ</strong>!';
		}

		if (violationCount >= MAX_VIOLATIONS) {
			warningMsg.innerHTML = '<strong style="color: #ff6b6b; font-size: 22px;">BẠN ĐÃ VI PHẠM QUÁ ' + MAX_VIOLATIONS + ' LẦN!</strong><br>Bài thi sẽ tự động nộp trong 5 giây...';
			overlay.classList.add('show');
			warning.classList.add('show');
			setTimeout(function () {
				alert('❌ Bài thi bị khóa do vi phạm quy định! Hệ thống sẽ tự động nộp bài.');
				quizForm.submit();
			}, 5000);
		} else {
			var remaining = MAX_VIOLATIONS - violationCount;
			if (type === 'exit_fullscreen') {
				warningMsg.innerHTML = 'Đây là vi phạm quy định làm bài thi.<br>Còn <strong style="color: #ff6b6b; font-size: 20px;">' + remaining + '</strong> lần cảnh cáo!<br><br><em style="color: #f59e0b;">Nếu bạn đã vào fullscreen, vui lòng vào lại bằng F11</em>';
			} else {
				warningMsg.innerHTML = 'Đây là vi phạm quy định làm bài thi.<br>Còn <strong style="color: #ff6b6b; font-size: 20px;">' + remaining + '</strong> lần cảnh cáo!';
			}
			overlay.classList.add('show');
			warning.classList.add('show');
		}
	}

	window.closeWarning = function () {
		document.getElementById('overlay').classList.remove('show');
		document.getElementById('cheatWarning').classList.remove('show');
		isWarningShown = false;
	};

	var closeWarningBtn = document.querySelector('.js-close-warning');
	if (closeWarningBtn) {
		closeWarningBtn.addEventListener('click', function () {
			window.closeWarning();
		});
	}

	var quizBackBtn = document.querySelector('.js-quiz-back');
	if (quizBackBtn) {
		quizBackBtn.addEventListener('click', function () {
			window.history.back();
		});
	}

	function handleFullscreenChange() {
		var inFS = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
		if (inFS) {
			isInFullscreen = true;
		} else if (isInFullscreen && isAntiCheatActive && !isSubmitting) {
			isInFullscreen = false;
			showWarning('exit_fullscreen');
		}
	}

	document.addEventListener('fullscreenchange', handleFullscreenChange);
	document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
	document.addEventListener('mozfullscreenchange', handleFullscreenChange);
	document.addEventListener('MSFullscreenChange', handleFullscreenChange);

	document.addEventListener('visibilitychange', function () {
		if (document.hidden && isAntiCheatActive && !isSubmitting) {
			showWarning('tab_switch');
		}
	});

	window.addEventListener('blur', function () {
		if (isAntiCheatActive && !isWarningShown && violationCount < MAX_VIOLATIONS && !isSubmitting) {
			showWarning('tab_switch');
		}
	});

	document.addEventListener('contextmenu', function (e) {
		if (isAntiCheatActive) {
			e.preventDefault();
			alert('❌ Không được phép sử dụng chuột phải trong khi làm bài!');
		}
	});

	document.addEventListener('keydown', function (e) {
		if (isAntiCheatActive && (
			e.key === 'F12' ||
			(e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'C' || e.key === 'J')) ||
			(e.ctrlKey && e.key === 'u')
		)) {
			e.preventDefault();
			alert('❌ Phím tắt này đã bị vô hiệu hóa trong khi làm bài!');
		}
	});

	if (timerBox && totalSeconds > 0) {
		function updateTimer() {
			var minutes = Math.floor(totalSeconds / 60);
			var seconds = totalSeconds % 60;
			timerDisplay.textContent =
				(minutes < 10 ? '0' : '') + minutes + ':' +
				(seconds < 10 ? '0' : '') + seconds;
			if (totalSeconds <= 300) timerBox.classList.add('warning');
			if (totalSeconds <= 0) {
				alert('Hết thời gian làm bài! Hệ thống sẽ tự động nộp bài.');
				quizForm.submit();
				return;
			}
			totalSeconds--;
			setTimeout(updateTimer, 1000);
		}
		updateTimer();
	}

	window.addEventListener('beforeunload', function (e) {
		if (violationCount < MAX_VIOLATIONS && !isSubmitting) {
			e.preventDefault();
			e.returnValue = 'Bạn có chắc muốn rời khỏi? Bài làm sẽ không được lưu!';
		}
	});

	quizForm.addEventListener('submit', function () {
		isSubmitting = true;
		isAntiCheatActive = false;
	});

	setTimeout(function () {
		alert('⚠️ HƯỚNG DẪN LÀM BÀI:\n\n📌 KHUYẾN KHÍCH:\n• Nhấn F11 để vào chế độ toàn màn hình (giảm phân tâm)\n• Nếu vào fullscreen, KHÔNG nên thoát ra (sẽ bị cảnh cáo)\n\n🚫 QUY ĐỊNH BẮT BUỘC:\n1. KHÔNG được chuyển tab/cửa sổ\n2. Vi phạm quá 3 lần → Tự động nộp bài\n3. KHÔNG được sử dụng chuột phải\n4. Một số phím tắt đã bị vô hiệu hóa\n\n✅ Nhấn OK để bắt đầu làm bài');
		setTimeout(function () {
			isAntiCheatActive = true;
		}, 1000);
	}, 200);
});

// ===== view_assignments.php =====
document.addEventListener('DOMContentLoaded', function () {
	if (!document.body.classList.contains('view-assignments-page')) return;

	document.querySelectorAll('.js-report-assignment').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var assignmentId = btn.dataset.assignmentId;
			var classId = btn.dataset.classId;
			var reason = prompt('Nhập lý do báo cáo bài tập này:');
			if (reason && reason.trim() !== '') {
				fetch('/E_learning/modules/report/report_process.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: 'assignment_id=' + assignmentId + '&reason=' + encodeURIComponent(reason) + '&class_id=' + classId
				})
				.then(function (response) { return response.json(); })
				.then(function (data) {
					if (data.success) {
						alert('Báo cáo của bạn đã được gửi!');
					} else {
						alert('Lỗi: ' + data.message);
					}
				})
				.catch(function () {
					alert('Đã xảy ra lỗi khi gửi báo cáo!');
				});
			}
		});
	});
});

// ===== create_assignment.php =====
function toggleSourceType() {
	var existingSection = document.getElementById('existingFileSection');
	var uploadSection = document.getElementById('uploadFileSection');
	var sourceType = document.querySelector('input[name="source_type"]:checked');
	if (!sourceType) return;

	if (sourceType.value === 'existing') {
		existingSection.style.display = 'block';
		uploadSection.style.display = 'none';
		document.getElementById('lecture_id').required = true;
		document.getElementById('uploadFile').required = false;
	} else {
		existingSection.style.display = 'none';
		uploadSection.style.display = 'block';
		document.getElementById('lecture_id').required = false;
		document.getElementById('uploadFile').required = true;
	}
}

function generateWithAI() {
	var form = document.getElementById('quizForm');
	var classId = form ? parseInt(form.dataset.classId || '0', 10) : 0;
	var sourceType = document.querySelector('input[name="source_type"]:checked');
	var numQ = document.getElementById('num_q').value;
	var difficulty = document.querySelector('select[name="difficulty"]').value;

	if (!numQ || numQ < 1 || numQ > 20) {
		return alert('Vui lòng nhập số câu hỏi từ 1 đến 20!');
	}
	if (!difficulty) {
		return alert('Vui lòng chọn mức độ bài tập!');
	}

	document.getElementById('loading').style.display = 'block';
	document.getElementById('reviewZone').style.display = 'none';

	if (sourceType && sourceType.value === 'existing') {
		var lectId = document.getElementById('lecture_id').value;
		if (!lectId) {
			document.getElementById('loading').style.display = 'none';
			return alert('Vui lòng chọn giáo trình!');
		}
		fetch('/E_learning/modules/quiz/api_generate_quiz.php?lecture_id=' + lectId + '&num=' + numQ + '&class_id=' + classId + '&difficulty=' + difficulty)
			.then(function (res) { return res.json(); })
			.then(displayQuizData)
			.catch(handleQuizError);
	} else {
		var fileInput = document.getElementById('uploadFile');
		if (!fileInput.files.length) {
			document.getElementById('loading').style.display = 'none';
			return alert('Vui lòng chọn file để upload!');
		}
		var file = fileInput.files[0];
		var allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
		var fileExtension = file.name.toLowerCase().split('.').pop();
		if (!allowedExtensions.includes(fileExtension)) {
			document.getElementById('loading').style.display = 'none';
			return alert('Định dạng file không được hỗ trợ! Chỉ chấp nhận: PDF, JPG, PNG');
		}
		if (file.size > 10 * 1024 * 1024) {
			document.getElementById('loading').style.display = 'none';
			return alert('File quá lớn! Vui lòng chọn file dưới 10MB.');
		}
		var formData = new FormData();
		formData.append('upload_file', file);
		formData.append('num', numQ);
		formData.append('class_id', classId);
		formData.append('difficulty', difficulty);
		fetch('/E_learning/modules/quiz/api_generate_quiz.php', { method: 'POST', body: formData })
			.then(function (res) {
				if (!res.ok) throw new Error('HTTP error! status: ' + res.status);
				return res.json();
			})
			.then(displayQuizData)
			.catch(handleQuizError);
	}
}

function displayQuizData(data) {
	document.getElementById('loading').style.display = 'none';
	if (data.error) {
		alert('❌ Lỗi từ AI: ' + data.error);
		return;
	}
	var questionArray = data.questions ? data.questions : data;
	if (!Array.isArray(questionArray)) {
		alert('❌ Dữ liệu AI trả về không đúng định dạng mảng!\n\nDữ liệu nhận được: ' + JSON.stringify(data).substring(0, 200));
		return;
	}
	if (questionArray.length === 0) {
		alert('⚠️ AI không tạo được câu hỏi nào. Vui lòng thử lại hoặc chọn file khác.');
		return;
	}
	var html = '';
	questionArray.forEach(function (item, i) {
		if (!item.question || !item.options || !Array.isArray(item.options)) return;
		html += '<div class="quiz-item"><label>Câu ' + (i + 1) + ':</label>' +
			'<input type="text" name="questions[]" value="' + escapeHtml(item.question) + '" style="width:100%;">' +
			'<div style="margin-left: 20px; margin-top: 10px;">' +
			item.options.map(function (opt, j) {
				return '<div class="option-row">' +
					'<input type="radio" name="correct_index[' + i + ']" value="' + j + '"' + (j == item.correct_index ? ' checked' : '') + '>' +
					'<input type="text" name="options[' + i + '][]" value="' + escapeHtml(opt) + '" style="width:80%;">' +
					'</div>';
			}).join('') +
			'</div><p>' + escapeHtml(item.explanation || 'Không có giải thích') + '</p></div>';
	});
	if (html === '') {
		alert('⚠️ Không có câu hỏi hợp lệ nào được tạo. Vui lòng thử lại.');
		return;
	}
	document.getElementById('questionsContainer').innerHTML = html;
	document.getElementById('reviewZone').style.display = 'block';
}

function escapeHtml(text) {
	var div = document.createElement('div');
	div.textContent = text || '';
	return div.innerHTML;
}

function handleQuizError(err) {
	document.getElementById('loading').style.display = 'none';
	alert('❌ Lỗi kết nối đến máy chủ!\n\n' + err.message + '\n\nVui lòng kiểm tra:\n- Kết nối internet\n- File api_generate_quiz.php\n- Console (F12) để xem chi tiết');
}

document.addEventListener('DOMContentLoaded', function () {
	if (!document.body.classList.contains('create-assignment-page')) return;

	document.querySelectorAll('input[name="source_type"]').forEach(function (radio) {
		radio.addEventListener('change', toggleSourceType);
	});

	var generateBtn = document.querySelector('.js-generate-quiz');
	if (generateBtn) {
		generateBtn.addEventListener('click', generateWithAI);
	}

	toggleSourceType();
});
