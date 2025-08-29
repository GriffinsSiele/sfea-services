<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:str="http://exslt.org/strings">
	<xsl:output method="html"/>
	<!--Обрабатываем данные ответа-->
	<xsl:template match="/Response">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
				<title>
					<xsl:value-of select="./Request/PersonReq/paternal"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/PersonReq/first"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/PersonReq/middle"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/PersonReq/birthDt"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/PersonReq/passport_series"/>
					<xsl:value-of select="./Request/PersonReq/passport_number"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/PersonReq/driver_number"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/PhoneReq/phone"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/EmailReq/email"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/URLReq/url"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/CarReq/vin"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/CarReq/regnum"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/CarReq/ctc"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/IPReq/ip"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="./Request/OrgReq/inn"/>
				</title>
                                <link rel="stylesheet" href="view.css"/>
				<script src="https://api-maps.yandex.ru/2.0-stable/?load=package.standard&amp;lang=ru-RU" type="text/javascript"/>
			</head>
			<body>
                                <h2>
														<xsl:value-of select="./Request/PersonReq/paternal"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/first"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/middle"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/birthDt"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/passport_series"/>
														<xsl:value-of select="./Request/PersonReq/passport_number"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/driver_number"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PhoneReq/phone"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/EmailReq/email"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/URLReq/url"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/CarReq/vin"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/CarReq/regnum"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/CarReq/ctc"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/IPReq/ip"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/OrgReq/inn"/>
                                </h2>
				<hr/>
				<table cellSpacing="0" width="100%">
					<tr>
						<td class="Table100_80">
							<table border="0" width="100%" cellSpacing="0">
								<!--tr>
									<td width="100%">
										<font size="+1">Запрос</font>
										<hr/>
									</td>
								</tr-->
								<xsl:for-each select=".">
									<tr>
										<td width="100%">
											<table>
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="eeeeee">
								  Идентификатор запроса
													</td>
													<td bgcolor="eeeeee" width="330px">
														<xsl:value-of select="./Request/requestId"/>
													</td>
												</tr>
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">
								  Дата и время запроса
	  												</td>
													<td class="date" bgcolor="c0c0c0" width="330px">
														<xsl:value-of select="concat(substring(./Request/requestDateTime,9,2),'.',substring(./Request/requestDateTime,6,2),'.',substring(./Request/requestDateTime,1,4),' ',substring(./Request/requestDateTime,12,8))"/>
													</td>
												</tr>
												<!--tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">
								  Пользователь
													</td>
													<td bgcolor="c0c0c0" width="330px">
														<xsl:value-of select="./Request/UserID"/>
													</td>
												</tr-->
												<!--tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="eeeeee">
								  IP-адрес
													</td>
													<td bgcolor="eeeeee" width="330px">
														<xsl:value-of select="./Request/UserIP"/>
													</td>
												</tr-->
												<!--tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">
								  Запрос по
													</td>
													<td bgcolor="c0c0c0" width="330px">
														<xsl:value-of select="./Request/PersonReq/paternal"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/first"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/middle"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/birthDt"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/passport_series"/>
														<xsl:value-of select="./Request/PersonReq/passport_number"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PersonReq/driver_number"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/PhoneReq/phone"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/EmailReq/email"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/URLReq/url"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/CarReq/vin"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/CarReq/regnum"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/CarReq/ctc"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/IPReq/ip"/>
														<xsl:text> </xsl:text>
														<xsl:value-of select="./Request/OrgReq/inn"/>
													</td>
												</tr-->
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="eeeeee">
								  Коды источников
													</td>
													<td bgcolor="eeeeee" width="330px">
														<xsl:value-of select="./Request/sources"/>
													</td>
												</tr>
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">
								  Идентификатор отчета
													</td>
													<td bgcolor="c0c0c0" width="330px">
														<xsl:value-of select="@id"/>
													</td>
												</tr>
												<!--tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="eeeeee">
								  Дата получения отчета
													</td>
													<td class="date" bgcolor="eeeeee" width="330px">
														<xsl:value-of select="concat(substring(@datetime,9,2),'.',substring(@datetime,6,2),'.',substring(@datetime,1,4),' ',substring(@datetime,12,8))"/>
													</td>
												</tr-->
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="eeeeee">
								  Ссылка на отчет
													</td>
													<td bgcolor="eeeeee" width="330px">
														<a href="{@view}&amp;mode=xml" target="_blank">XML</a>, <a href="{@view}" target="_blank">HTML</a>, <a href="{@view}&amp;mode=pdf" target="_blank">PDF</a>
                                                                                                                <input type="hidden" id="url" value="{@view}"/>
													</td>
												</tr>
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">
								  Статус
													</td>
													<td bgcolor="c0c0c0" width="330px">
														<xsl:if test="@status=0">Выполняется</xsl:if>
														<xsl:if test="@status=1">Выполнен</xsl:if>
                                                                                                                <input type="hidden" id="status" value="{@status}"/>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</xsl:for-each>
								<!-- Документы Identification-->
							</table>
						</td>
					</tr>
				</table>
				<table cellSpacing="0" width="100%">
					<tr>
						<td class="Table100_80">
							<table border="0" width="100%" cellSpacing="0">
								<!--Персональные данные PersonReply -->
								<xsl:variable name="PersonReply">
									<i key="name1">Фамилия</i>
									<i key="first">Имя</i>
									<i key="paternal">Отчество</i>
									<i key="birthDt">Дата рождения</i>
									<i key="countryBirth">Страна рождения</i>
									<i key="deathFlag">Скончался</i>
									<i key="placeOfBirth">Место рождения</i>
									<i key="oldSurname">Фамилия до изменения</i>
									<i key="oldFirstName">Имя до изменения</i>
									<i key="nationalityText">Гражданство</i>
									<i key="nationality">Национальность(страна)</i>
									<i key="maritalStatusText">Семейное положение</i>
									<i key="genderText">Пол</i>
								</xsl:variable>
								<xsl:for-each select="./PersonReply">
									<tr>
										<td width="100%">
											<hr/>
											<font size="+1">Личные данные</font>
											<hr/>
										</td>
									</tr>
									<tr>
										<td>
											<table>
												<xsl:for-each select="*">
													<xsl:variable name="tag_name" select="name()"/>
													<tr>
														<xsl:if test="position() mod 2 = 0">
															<xsl:attribute name="bgcolor">#cccccc</xsl:attribute>
														</xsl:if>
														<xsl:if test="position() mod 2 != 0">
															<xsl:attribute name="bgcolor">#eeeeee</xsl:attribute>
														</xsl:if>
														<td style="width: 50px" bgcolor="FFFFFF"/>
														<td width="200px">
															<xsl:value-of select="$tag_name"/>
														</td>
														<td width="330px">
															<xsl:value-of select="."/>
														</td>
													</tr>
												</xsl:for-each>
											</table>
										</td>
									</tr>
								</xsl:for-each>
								<!-- Документы Identification-->
								<xsl:variable name="IdReply">
									<i key="idTypeText">Тип</i>
									<i key="seriesNumber">Серия</i>
									<i key="idNum">Номер</i>
									<i key="issueAuthority">Выдан</i>
									<i key="issueCountry">Место выдачи</i>
									<i key="issueDate">Дата выдачи</i>
									<i key="idStatusText">Статус</i>
									<i key="idStatusRaw">Полный статус</i>
								</xsl:variable>
								<xsl:for-each select="./IdReply">
									<tr>
										<td width="100%">
											<hr/>
											<font size="+1">Документы</font>
											<hr/>
											<table>
												<xsl:for-each select="*">
													<xsl:variable name="tag_name" select="name()"/>
													<tr>
														<xsl:if test="position() mod 2 = 0">
															<xsl:attribute name="bgcolor">#cccccc</xsl:attribute>
														</xsl:if>
														<xsl:if test="position() mod 2 != 0">
															<xsl:attribute name="bgcolor">#eeeeee</xsl:attribute>
														</xsl:if>
														<td style="width: 50px" bgcolor="FFFFFF"/>
														<td width="200px">
															<xsl:value-of select="$tag_name"/>
														</td>
														<td width="330px">
															<xsl:value-of select="."/>
														</td>
													</tr>
												</xsl:for-each>
											</table>
										</td>
									</tr>
								</xsl:for-each>
								<!-- Место работы Employment-->
								<xsl:variable name="EmploymentReply">
									<i key="endDt">Дата увольнения</i>
									<i key="name">Работодатель</i>
									<i key="occupationStatusText">Занятость</i>
									<i key="occupationText">Род занятий</i>
									<i key="occupationTradeText">Отрасль</i>
									<i key="startDt">Дата принятия на работу</i>
									<i key="titleText">Должность</i>
								</xsl:variable>
								<xsl:for-each select="./EmploymentReply">
									<tr>
										<hr/>
										<td width="100%">
											<font size="+1">Место работы</font>
											<hr/>
											<table>
												<xsl:for-each select="*">
													<xsl:variable name="tag_name" select="name()"/>
													<tr>
														<xsl:if test="position() mod 2 = 0">
															<xsl:attribute name="bgcolor">#cccccc</xsl:attribute>
														</xsl:if>
														<xsl:if test="position() mod 2 != 0">
															<xsl:attribute name="bgcolor">#eeeeee</xsl:attribute>
														</xsl:if>
														<td style="width: 50px" bgcolor="FFFFFF"/>
														<td width="200px">
															<xsl:value-of select="$tag_name"/>
														</td>
														<td width="230">
															<xsl:value-of select="."/>
														</td>
													</tr>
												</xsl:for-each>
											</table>
										</td>
									</tr>
								</xsl:for-each>
								<!-- Источники Source-->
								<xsl:variable name="SourceReply">
									<i key="Name">Источник</i>
									<i key="Title">Название</i>
									<i key="Description">Описание</i>
									<i key="ResultText">Результат</i>
									<i key="Created">Дата создания</i>
									<i key="Updated">Дата обновления</i>
									<i key="Deleted">Дата удаления</i>
								</xsl:variable>
								<xsl:for-each select="./Source">
									<tr>
										<td width="100%" class="source {./Name} {@checktype}">
											<hr/>
											<h2><xsl:if test="./CheckTitle"><xsl:value-of select="./CheckTitle"/></xsl:if><xsl:if test="not(./CheckTitle)"><xsl:value-of select="./Title"/></xsl:if></h2>
											<hr/>
											<table>
												<!--tr>
													<td style="width: 50px"/>
													<td width="50px" bgcolor="eeeeee">Источник</td>
													<td colspan="2" bgcolor="eeeeee">
														<xsl:value-of select="./Name"/>
													</td>
												</tr>
												<tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">Название источника</td>
													<td colspan="2" bgcolor="c0c0c0">
														<xsl:value-of select="./Title"/>
													</td>
												</tr-->
												<!--tr>
													<td style="width: 50px"/>
													<td width="50px" bgcolor="eeeeee">ID проверки</td>
													<td colspan="2" bgcolor="eeeeee">
														<xsl:value-of select="@request_id"/>
													</td>
												</tr-->
												<!--tr>
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">Время обработки, с</td>
													<td colspan="2" bgcolor="c0c0c0">
														<xsl:value-of select="@process_time"/>
													</td>
												</tr-->
												<tr>
													<td style="width: 50px"/>
													<td width="50px" bgcolor="eeeeee">Запрос</td>
													<td colspan="2" bgcolor="eeeeee">
														<xsl:value-of select="./Request"/>
													</td>
												</tr>
												<xsl:if test="./ResultsCount"><tr>
													<xsl:if test="./ResultsCount!=0">
														<xsl:attribute name="class">
															<xsl:value-of select="'resultscount found'"/>
														</xsl:attribute>
													</xsl:if>
													<xsl:if test="./ResultsCount=0">
														<xsl:attribute name="class">
															<xsl:value-of select="'resultscount notfound'"/>
														</xsl:attribute>
													</xsl:if>
													<td style="width: 50px"/>
													<td width="50px" bgcolor="c0c0c0">Найдено</td>
													<td colspan="2" bgcolor="c0c0c0">
														<xsl:value-of select="./ResultsCount"/>
													</td>
												</tr></xsl:if>
												<!--xsl:for-each select="*">
													<xsl:variable name="tag_name" select="name()"/><xsl:if test="($tag_name!='Name')and($tag_name!='Title')and($tag_name!='CheckTitle')and($tag_name!='Request')and($tag_name!='ResultsCount')and($tag_name!='Error')and($tag_name!='Record')and($tag_name!='Contact')">
													<tr>
														<xsl:if test="position() mod 2 = 0">
															<xsl:attribute name="bgcolor">#cccccc</xsl:attribute>
														</xsl:if>
														<xsl:if test="position() mod 2 != 0">
															<xsl:attribute name="bgcolor">#eeeeee</xsl:attribute>
														</xsl:if>
														<td style="width: 50px" bgcolor="FFFFFF"/>
														<td width="200px">
															<xsl:value-of select="$tag_name"/>
														</td>
														<td colspan="2">
															<xsl:value-of select="."/>
														</td>
													</tr></xsl:if>
												</xsl:for-each-->
												<xsl:if test="./Error"><tr>
													<td style="width: 50px"/>
													<td width="50px" class="error">Ошибка</td>
													<td colspan="2" class="error">
														<xsl:value-of select="./Error"/>
													</td>
												</tr></xsl:if>
												<!--xsl:for-each select="./Contact">
													<tr>
														<xsl:if test="position() mod 2 = 0">
															<xsl:attribute name="bgcolor">#cccccc</xsl:attribute>
														</xsl:if>
														<xsl:if test="position() mod 2 != 0">
															<xsl:attribute name="bgcolor">#eeeeee</xsl:attribute>
														</xsl:if>
														<td style="width: 50px" bgcolor="FFFFFF"/>
														<td>
															<xsl:value-of select="./ContactTitle"/>
														</td>
														<td>
																			<xsl:if test="ContactType='url'">
																				<a href="{./ContactId}" target="_blank"><xsl:value-of select="./ContactId"/></a>
																			</xsl:if>
																			<xsl:if test="ContactType='phone'">
																				<a href="callto:+{./ContactId}">+<xsl:value-of select="./ContactId"/></a>
																			</xsl:if>
																			<xsl:if test="ContactType='email'">
																				<a href="mailto:{./ContactId}"><xsl:value-of select="./ContactId"/></a>
																			</xsl:if>
														</td>
													</tr>
												</xsl:for-each-->
												<xsl:for-each select="./Record">
													<xsl:variable name="record_number" select="position()"/>
													<tr>
														<td style="width: 50px"/>
														<td colspan="3" bgcolor="FFFFFF">
															<b>
																<xsl:value-of select="./RecordDescription"/>
															</b>
														</td>
													</tr>
													<tr>
														<td style="width: 50px" bgcolor="FFFFFF"/>
														<td colspan="3" bgcolor="FFFFFF">
                                                                                                                        <hr/>
															<table cellspacing="0">
																<xsl:for-each select="./Field">
																	<tr>
																		<xsl:if test="position() mod 2 = 0">
																			<xsl:attribute name="bgcolor">#cccccc</xsl:attribute>
																		</xsl:if>
																		<xsl:if test="position() mod 2 != 0">
																			<xsl:attribute name="bgcolor">#eeeeee</xsl:attribute>
																		</xsl:if>
																		<td style="width: 50px" bgcolor="FFFFFF"/>
																		<td class="field_title {./FieldName}">
																			<xsl:value-of select="./FieldDescription"/>
																		</td>
																		<td style="width: 50px" />
																		<td class="field_value {./FieldName}">
																			<xsl:if test="FieldType='string' or FieldType='integer' or FieldType='float' or FieldType='boolean'">
																				<xsl:value-of select="./FieldValue"/>
																			</xsl:if>
																			<xsl:if test="FieldType='hidden'">
																				<xsl:value-of select="./FieldValue"/>
																			</xsl:if>
																			<xsl:if test="FieldType='datetime' or FieldType='date'">
																				<xsl:value-of select="./FieldValue"/>
																			</xsl:if>
																			<xsl:if test="FieldType='image'">
																				<img src="{./FieldValue}"/><br/>
																				<xsl:if test="starts-with(./FieldValue,'http')">
																					<a href="https://yandex.ru/images/search?rpt=imageview&amp;img_url={str:encode-uri(./FieldValue, 'true', 'UTF-8')}" target="_blank">Поиск в Яндекс</a><br/>
																					<!--a href="https://www.google.ru/searchbyimage?image_url={str:encode-uri(./FieldValue, 'true', 'UTF-8')}" target="_blank">Поиск в Google</a><br/-->
																				</xsl:if>
																			</xsl:if>
																			<xsl:if test="FieldType='url'">
																				<a href="{./FieldValue}" target="_blank"><xsl:value-of select="./FieldValue"/></a>
																			</xsl:if>
																			<xsl:if test="FieldType='phone'">
																				<a href="checkphone.php?phone={./FieldValue}" target="_blank"><xsl:value-of select="./FieldValue"/></a>
																			</xsl:if>
																			<xsl:if test="FieldType='email'">
																				<a href="checkemail.php?email={./FieldValue}" target="_blank"><xsl:value-of select="./FieldValue"/></a>
																			</xsl:if>
																			<xsl:if test="FieldType='skype'">
																				<a href="checkskype.php?skype={./FieldValue}" target="_blank"><xsl:value-of select="./FieldValue"/></a>
																			</xsl:if>
																			<xsl:if test="FieldType='nick'">
																				<a href="checknick.php?nick={./FieldValue}" target="_blank"><xsl:value-of select="./FieldValue"/></a>
																			</xsl:if>
																			<xsl:if test="FieldType='text'">
																				<pre><xsl:value-of select="./FieldValue"/></pre>
																			</xsl:if>
																			<xsl:if test="FieldType='address'">
																				<a href="https://yandex.ru/maps/?mode=search&amp;text={./FieldValue}" target="_blank"><xsl:value-of select="./FieldValue"/></a>
																			</xsl:if>
																			<xsl:if test="FieldType='map'">
																				<!--pre><xsl:value-of select="./FieldValue"/></pre-->
<div id="map_{../../@request_id}_{$record_number}_{./FieldName}" style="width: 600px; height: 400px"></div>
<script type="text/javascript">
    var Places_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/> = <xsl:value-of select="./FieldValue"/>;
    var Map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>;
    ymaps.ready(init_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>);

    function init_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>(){     
        var Col = new ymaps.GeoObjectCollection({},{preset: 'twirl#redIcon'});
        var Rect = [[90,180],[-90,-180]];
        Places_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>.forEach(function (point) {
            point.coords = [parseFloat(point.coords[0].toString().replace(",",".")),parseFloat(point.coords[1].toString().replace(",","."))];
            Placemark = new ymaps.Placemark(
                point.coords,
                {balloonContent: point.text}
            );
            Col.add(Placemark);
            if (Rect[0][0]&gt;point.coords[0]) Rect[0][0]=point.coords[0];
            if (Rect[0][1]&gt;point.coords[1]) Rect[0][1]=point.coords[1];
            if (Rect[1][0]&lt;point.coords[0]) Rect[1][0]=point.coords[0];
            if (Rect[1][1]&lt;point.coords[1]) Rect[1][1]=point.coords[1];
        });
        center = [(Rect[0][0]+Rect[1][0])*0.5, (Rect[0][1]+Rect[1][1])*0.5];
        zoom = Math.floor(9-Math.log2(Math.max.apply(null,[(Rect[1][0]-Rect[0][0])*2,Rect[1][1]-Rect[0][1]])));
        if (zoom>14) zoom=14;
        Map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/> = new ymaps.Map ("map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>", {
            center: center,
            zoom: zoom
        });
        Map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>.controls.add('mapTools');
        Map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>.controls.add('typeSelector');
        Map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>.controls.add('zoomControl');
        Map_<xsl:value-of select="../../@request_id"/>_<xsl:value-of select="$record_number"/>_<xsl:value-of select="./FieldName"/>.geoObjects.add(Col);
    }
</script>
																			</xsl:if>
																		</td>
																	</tr>
																</xsl:for-each>
															</table>
														</td>
													</tr>
												</xsl:for-each>
											</table>
										</td>
									</tr>
								</xsl:for-each>
								<!-- Решение Decision -->
								<xsl:variable name="Decision">
									<i key="code">Код решения</i>
								</xsl:variable>
								<xsl:for-each select="./Decision">
									<tr>
										<td width="100%" class="decision">
											<hr/>
											<h2>Решение по запросу</h2>
											<hr/>
											<table>
												<tr class="result">
													<td style="width: 50px"/>
													<td width="200px" bgcolor="c0c0c0">Код решения</td>
													<td colspan="2" bgcolor="c0c0c0">
														<xsl:value-of select="./Result"/>
													</td>
												</tr>
												<xsl:for-each select="./RulesLog/Rule">
													<xsl:variable name="record_number" select="position()"/>
													<tr>
														<td style="width: 50px"/>
														<td colspan="3" bgcolor="FFFFFF">
															<table cellspacing="0">
																<xsl:if test="./RuleCode">
																	<tr class="rulecode" bgcolor="eeeeee">
																		<td style="width: 50px" bgcolor="FFFFFF" />
																		<td>Код правила</td>
																		<td style="width: 50px" />
																		<td>
																			<xsl:value-of select="./RuleCode"/>
																		</td>
																	</tr>
																</xsl:if>
																<xsl:if test="./RuleResult">
																	<tr class="ruleresult" bgcolor="cccccc">
																		<td style="width: 50px" bgcolor="FFFFFF" />
																		<td>Результат выполнения</td>
																		<td style="width: 50px" />
																		<td>
																			<xsl:value-of select="./RuleResult"/>
																		</td>
																	</tr>
																</xsl:if>
																<xsl:if test="./RuleMessage">
																	<tr class="rulemessage" bgcolor="eeeeee">
																		<td style="width: 50px" bgcolor="FFFFFF" />
																		<td>Сообщение правила</td>
																		<td style="width: 50px" />
																		<td>
																			<xsl:value-of select="./RuleMessage"/>
																		</td>
																	</tr>
																</xsl:if>
															</table>
														</td>
													</tr>
												</xsl:for-each>
											</table>
										</td>
									</tr>
								</xsl:for-each>
							</table>
						</td>
					</tr>
				</table>
				<br/><br/><hr/>
				<xsl:if test="@status=1">
                                Отчет подготовлен $servicename <xsl:value-of select="concat(substring(@datetime,9,2),'.',substring(@datetime,6,2),'.',substring(@datetime,1,4),' в ',substring(@datetime,12,8))"/><br/>
				</xsl:if>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
